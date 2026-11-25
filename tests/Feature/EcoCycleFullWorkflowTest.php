<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\MarketplaceReview;
use App\Models\MaterialExchange;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EcoCycleFullWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_full_workflow_secret_999';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config()->set('services.stripe.key', 'pk_test_workflow');
        config()->set('services.stripe.secret', 'sk_test_workflow');
        config()->set('services.stripe.webhook_secret', $this->webhookSecret);
        config()->set('services.stripe.currency', 'myr');
        config()->set('services.gemini.api_key', '');
    }

    private function createApprovedBusiness(string $name, string $address = 'No. 10, Jalan Perindustrian 3', string $city = 'Shah Alam', string $state = 'Selangor'): User
    {
        return User::factory()->create([
            'business_name' => $name,
            'business_registration_number' => fake()->unique()->numerify('202501######'),
            'account_status' => 'approved',
            'registered_address' => $address,
            'city' => $city,
            'postcode' => '40200',
            'state' => $state,
            'business_phone' => '03-55119988',
        ]);
    }

    private function sendSignedStripeWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $json = (string) json_encode($payload);
        $sig = StripePaymentService::buildTestSignatureHeader($json, $this->webhookSecret);

        return $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'],
            $json
        );
    }

    public function test_part_1_and_2_ai_analysis_8_reusable_suggestions_update_item_details_and_separate_publish(): void
    {
        $seller = $this->createApprovedBusiness('SolarWood Industries Sdn Bhd', 'Lot 88, Kawasan Perindustrian Rawang');

        $photo = UploadedFile::fake()->image('pine_planks.jpg', 640, 480);

        // 1. Analyze Item returns all 8 Reusable AI suggestion fields
        $analyzeResp = $this->actingAs($seller)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Kiln-Dried Pine Timber Planks',
            'description' => 'Clean unused construction-grade pine planks stored indoors.',
            'quantity' => 25,
            'unit' => 'pcs',
            'condition' => 'New',
            'image_1' => $photo,
        ]);

        $analyzeResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('analysis.suggested_reusability', 'reusable')
            ->assertJsonStructure([
                'analysis' => [
                    'suggested_category',
                    'recommended_actions',
                    'reuse_applications',
                    'suggested_condition',
                    'suggested_listing_type',
                    'suggested_listing_type_label',
                    'suggested_price_range',
                    'reason',
                    'other_recommendations',
                ],
                'saved_image_slots',
                'analysis_token',
            ]);

        $payload = $analyzeResp->json();

        // 2. Clicking "Update Item Details" saves item to inventory (NOT published yet) and redirects to Edit page
        $storeResp = $this->actingAs($seller)->post(route('business.inventory.store'), [
            'name' => 'Kiln-Dried Pine Timber Planks',
            'description' => 'Clean unused construction-grade pine planks stored indoors.',
            'quantity' => 25,
            'unit' => 'pcs',
            'condition' => 'New',
            'category' => $payload['analysis']['suggested_category'],
            'reusability' => 'reusable',
            'saved_image_slots' => json_encode($payload['saved_image_slots']),
            'analysis_token' => $payload['analysis_token'],
            'ai_analysis_json' => json_encode($payload['analysis']),
            'redirect_to' => 'edit',
        ]);

        $item = InventoryItem::where('name', 'Kiln-Dried Pine Timber Planks')->firstOrFail();
        $storeResp->assertRedirect(route('business.inventory.edit', $item));
        $this->assertEquals(25.0, (float) $item->original_quantity);
        $this->assertEquals(25.0, (float) $item->quantity);
        $this->assertFalse($item->isPublishedToMarketplace());
        $this->assertIsArray($item->ai_analysis);

        // 3. Update Inventory Details page shows Material Analysis summary (no Google Gemini/API wording), Sell/Free/Lend, Price per Unit, and default business address
        $this->actingAs($seller)
            ->get(route('business.inventory.edit', $item))
            ->assertOk()
            ->assertSee('Update Inventory Details')
            ->assertSee('Material Analysis')
            ->assertDontSee('Google Gemini')
            ->assertSee('Price per Unit (RM)')
            ->assertSee('Lot 88, Kawasan Perindustrian Rawang');

        // 4. Owner sets Sell + Price per Unit RM 18.50, saves, then explicitly publishes to Marketplace
        $this->actingAs($seller)->put(route('business.inventory.update', $item), [
            'name' => 'Kiln-Dried Pine Timber Planks',
            'description' => 'Clean unused construction-grade pine planks.',
            'category' => 'Timber & Wood Products',
            'quantity' => 25,
            'unit' => 'pcs',
            'condition' => 'New',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 18.50,
            'pickup_address' => 'Lot 88, Kawasan Perindustrian Rawang, 40200 Shah Alam, Selangor',
            'redirect_to' => 'edit',
        ])->assertRedirect(route('business.inventory.edit', $item));

        $this->assertFalse($item->fresh()->isPublishedToMarketplace());

        $this->actingAs($seller)
            ->post(route('business.inventory.publish', $item))
            ->assertRedirect(route('business.marketplace'));

        $this->assertTrue($item->fresh()->isPublishedToMarketplace());
        $this->assertEquals(18.50, (float) $item->fresh()->price);
    }

    public function test_part_3_4_5_seller_5kg_sold_out_permanent_lock_and_new_8kg_submission_with_preserved_history(): void
    {
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::sequence()
                ->push(['id' => 'cs_seller_a_1', 'url' => 'https://checkout.stripe.com/c/pay/cs_seller_a_1', 'payment_intent' => 'pi_a_1'], 200)
                ->push(['id' => 'cs_seller_a_2', 'url' => 'https://checkout.stripe.com/c/pay/cs_seller_a_2', 'payment_intent' => 'pi_a_2'], 200),
        ]);

        $sellerA = $this->createApprovedBusiness('Seller A Aluminium Sdn Bhd', 'No. 5, Jalan Logam, Seksyen 15');
        $buyerA = $this->createApprovedBusiness('Buyer A Fabrication Sdn Bhd');
        $buyerB = $this->createApprovedBusiness('Buyer B Builders Sdn Bhd');
        $buyerC = $this->createApprovedBusiness('Buyer C Engineering Sdn Bhd');

        // Seller A creates & publishes 5 kg at RM 10.00/kg
        $item5kg = Item::create([
            'business_id' => $sellerA->id,
            'name' => 'Clean Aluminium Extrusion Bars',
            'title' => 'Clean Aluminium Extrusion Bars',
            'description' => 'Reusable architectural grade aluminium bars.',
            'category' => 'Metals',
            'quantity' => 5.00,
            'original_quantity' => 5.00,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'is_free' => false,
            'price' => 10.00,
            'condition' => 'New',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
            'pickup_address' => 'No. 5, Jalan Logam, Seksyen 15, 40200 Shah Alam, Selangor',
            'status' => 'active',
        ]);

        // Buyer A orders 3 kg (RM 30.00)
        $this->actingAs($buyerA)->post(route('business.marketplace.exchange', $item5kg), [
            'quantity' => 3,
            'proceed_to_checkout' => 1,
        ])->assertRedirect();

        $orderA = MaterialExchange::where('requester_business_id', $buyerA->id)->firstOrFail();
        $this->assertEquals(30.00, (float) $orderA->total_price);
        $this->assertEquals(5.00, (float) $item5kg->fresh()->quantity); // NOT deducted before Stripe verification!

        // Checkout page shows Seller Business Name & Seller Address at top
        $this->actingAs($buyerA)
            ->get(route('business.orders.checkout', $orderA))
            ->assertOk()
            ->assertSee('Seller A Aluminium Sdn Bhd')
            ->assertSee('No. 5, Jalan Logam, Seksyen 15')
            ->assertSee('RM 10.00 / kg')
            ->assertSee('RM 30.00');

        $this->actingAs($buyerA)->post(route('business.payments.stripe.checkout', $orderA))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_seller_a_1');
        $paymentA = Payment::where('material_exchange_id', $orderA->id)->firstOrFail();

        // Failed payment webhook must NOT deduct stock
        $this->sendSignedStripeWebhook([
            'id' => 'evt_fail_buyer_a',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_a_1',
                    'metadata' => ['exchange_id' => (string) $orderA->id, 'payment_id' => (string) $paymentA->id],
                ],
            ],
        ])->assertOk();
        $this->assertEquals(5.00, (float) $item5kg->fresh()->quantity);

        // Verified Paid webhook for Buyer A -> stock becomes 2 kg, delivery_status = confirmed (NOT auto-delivered!)
        $paidWebhookA = [
            'id' => 'evt_paid_buyer_a',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_seller_a_1',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_a_1',
                    'currency' => 'myr',
                    'amount_total' => 3000,
                    'metadata' => ['exchange_id' => (string) $orderA->id, 'payment_id' => (string) $paymentA->id],
                ],
            ],
        ];
        $this->sendSignedStripeWebhook($paidWebhookA)->assertOk()->assertJsonPath('status', 'paid');
        // Duplicate webhook ignored — stock stays 2 kg
        $this->sendSignedStripeWebhook($paidWebhookA)->assertOk()->assertJsonPath('status', 'duplicate_event_ignored');

        $this->assertEquals(2.00, (float) $item5kg->fresh()->quantity);
        $this->assertSame('active', $item5kg->fresh()->status);
        $this->assertSame('confirmed', $orderA->fresh()->delivery_status);
        $this->assertDatabaseHas('order_status_history', [
            'material_exchange_id' => $orderA->id,
            'status' => 'confirmed',
        ]);

        // Buyer B tries to oversell by requesting 3 kg when only 2 kg remain -> blocked!
        $this->actingAs($buyerB)->post(route('business.marketplace.exchange', $item5kg), [
            'quantity' => 3,
        ])->assertSessionHasErrors('quantity');

        // Buyer C purchases remaining 2 kg (RM 20.00)
        $this->actingAs($buyerC)->post(route('business.marketplace.exchange', $item5kg), [
            'quantity' => 2,
            'proceed_to_checkout' => 1,
        ])->assertRedirect();

        $orderC = MaterialExchange::where('requester_business_id', $buyerC->id)->firstOrFail();
        $this->actingAs($buyerC)->post(route('business.payments.stripe.checkout', $orderC))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_seller_a_2');
        $paymentC = Payment::where('material_exchange_id', $orderC->id)->firstOrFail();

        $this->sendSignedStripeWebhook([
            'id' => 'evt_paid_buyer_c',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_seller_a_2',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_a_2',
                    'currency' => 'myr',
                    'amount_total' => 2000,
                    'metadata' => ['exchange_id' => (string) $orderC->id, 'payment_id' => (string) $paymentC->id],
                ],
            ],
        ])->assertOk()->assertJsonPath('status', 'paid');

        // Item is now 0 kg and remains visible as permanently sold out.
        $item5kg->refresh();
        $this->assertEquals(0.00, (float) $item5kg->quantity);
        $this->assertSame('sold', $item5kg->getRawOriginal('status'));
        $this->assertTrue($item5kg->isPermanentlySoldOut());

        $this->actingAs($buyerB)->get(route('business.marketplace'))
            ->assertOk()
            ->assertSee('Clean Aluminium Extrusion Bars')
            ->assertSee('disabled>Out of Stock</button>', false);

        // Permanent Sold Out Rule: Seller A CANNOT restock, edit quantity > 0, or republish old submission
        $this->actingAs($sellerA)->post(route('business.inventory.adjust', $item5kg), [
            'type' => 'stock_in',
            'quantity' => 8,
        ])->assertSessionHasErrors('quantity');

        $this->actingAs($sellerA)->put(route('business.inventory.update', $item5kg), [
            'name' => 'Clean Aluminium Extrusion Bars',
            'category' => 'Metals',
            'quantity' => 8,
            'unit' => 'kg',
            'condition' => 'New',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 10.00,
        ])->assertSessionHasErrors('quantity');

        $this->actingAs($sellerA)->get(route('business.inventory.edit', $item5kg))
            ->assertOk()
            ->assertSee('Back to Inventory')
            ->assertSee('Add New Item Submission')
            ->assertDontSee('Save Item')
            ->assertDontSee('>Cancel</a>', false);

        $this->actingAs($sellerA)->put(route('business.inventory.update', $item5kg), [
            'name' => 'Changed sold-out name',
            'category' => 'Metals',
            'quantity' => 0,
            'unit' => 'kg',
            'condition' => 'New',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 10.00,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame('Clean Aluminium Extrusion Bars', $item5kg->fresh()->name);

        $this->actingAs($sellerA)->post(route('business.inventory.publish', $item5kg))
            ->assertSessionHasErrors('quantity');

        $this->assertEquals(0.00, (float) $item5kg->fresh()->quantity);

        // Seller A adds a brand-new 8 kg submission as a NEW item
        $newPhoto = UploadedFile::fake()->image('alu_batch_2.jpg', 400, 300);
        $this->actingAs($sellerA)->post(route('business.inventory.store'), [
            'name' => 'Clean Aluminium Extrusion Bars Batch 2',
            'description' => 'Fresh surplus batch of 8 kg aluminium bars.',
            'quantity' => 8,
            'unit' => 'kg',
            'condition' => 'New',
            'category' => 'Metals',
            'reusability' => 'reusable',
            'images' => [$newPhoto],
        ])->assertRedirect(route('business.inventory'));

        $item8kg = InventoryItem::where('name', 'Clean Aluminium Extrusion Bars Batch 2')->firstOrFail();
        $this->assertNotSame($item5kg->id, $item8kg->id);
        $this->assertEquals(8.00, (float) $item8kg->original_quantity);
        $this->assertEquals(8.00, (float) $item8kg->quantity);

        // Old Purchase History (Buyer A & Buyer C) and Sales History (Seller A) remain intact & preserved!
        $this->actingAs($buyerA)->get(route('business.transactions', ['tab' => 'purchases']))
            ->assertOk()
            ->assertSee('Clean Aluminium Extrusion Bars')
            ->assertSee('Seller A Aluminium Sdn Bhd')
            ->assertSee('RM 30.00');

        $this->actingAs($buyerC)->get(route('business.transactions', ['tab' => 'purchases']))
            ->assertOk()
            ->assertSee('Clean Aluminium Extrusion Bars')
            ->assertSee('RM 20.00');

        $this->actingAs($sellerA)->get(route('business.transactions', ['tab' => 'sales']))
            ->assertOk()
            ->assertSee('Buyer A Fabrication Sdn Bhd')
            ->assertSee('Buyer C Engineering Sdn Bhd');
    }

    public function test_part_6_free_claim_creates_zero_stripe_payments_and_deducts_stock_with_address(): void
    {
        Http::fake();

        $seller = $this->createApprovedBusiness('Free Packaging Hub Sdn Bhd');
        $buyer = $this->createApprovedBusiness('Community Reuse Works', 'No. 22, Jalan Reka, Petaling Jaya');

        $freeItem = Item::create([
            'business_id' => $seller->id,
            'name' => 'Surplus Bubble Wrap Rolls',
            'title' => 'Surplus Bubble Wrap Rolls',
            'description' => 'Clean unused packaging rolls free for pickup.',
            'category' => 'Packaging & paper',
            'quantity' => 10,
            'original_quantity' => 10,
            'unit' => 'pcs',
            'reusability' => 'reusable',
            'listing_type' => 'free',
            'is_free' => true,
            'price' => 0.00,
            'condition' => 'New',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
            'status' => 'active',
        ]);

        $claimResp = $this->actingAs($buyer)->post(route('business.marketplace.exchange', $freeItem), [
            'quantity' => 4,
            'fulfillment_method' => 'delivery',
            'collection_address' => 'No. 22, Jalan Reka, 46100 Petaling Jaya, Selangor',
        ]);

        $order = MaterialExchange::where('item_id', $freeItem->id)->firstOrFail();
        $claimResp->assertRedirect(route('business.orders.show', $order));

        Http::assertNothingSent();
        $this->assertSame(0, Payment::where('material_exchange_id', $order->id)->count());
        $this->assertSame('free', $order->payment_status);
        $this->assertSame('confirmed', $order->delivery_status);
        $this->assertSame('No. 22, Jalan Reka, 46100 Petaling Jaya, Selangor', $order->collection_address);
        $this->assertEquals(6.00, (float) $freeItem->fresh()->quantity);

        $receipt = Invoice::where('material_exchange_id', $order->id)->firstOrFail();
        $this->assertNull($receipt->payment_id);
        $this->assertEquals(0.00, (float) $receipt->total_amount);
        $this->assertSame('free', $receipt->payment_status);
    }

    public function test_part_2_and_7_lend_borrow_reserves_stock_and_seller_return_restores_stock(): void
    {
        $lender = $this->createApprovedBusiness('Lender Logistics Sdn Bhd');
        $borrower = $this->createApprovedBusiness('Borrower Events Sdn Bhd');

        $lendItem = Item::create([
            'business_id' => $lender->id,
            'name' => 'Collapsible Steel Stillage Cages',
            'title' => 'Collapsible Steel Stillage Cages',
            'description' => 'Available to borrow for warehouse transfer.',
            'category' => 'Metals',
            'quantity' => 5,
            'original_quantity' => 5,
            'unit' => 'pcs',
            'reusability' => 'reusable',
            'listing_type' => 'lend',
            'is_free' => true,
            'price' => 0.00,
            'condition' => 'Used',
            'location_city' => 'Klang',
            'location_state' => 'Selangor',
            'status' => 'active',
        ]);

        // Borrower borrows all 5 cages -> stock drops to 0 and status is 'inactive' (NOT permanently sold out!)
        $this->actingAs($borrower)->post(route('business.marketplace.exchange', $lendItem), [
            'quantity' => 5,
            'fulfillment_method' => 'collection',
        ])->assertRedirect();

        $borrowOrder = MaterialExchange::where('item_id', $lendItem->id)->firstOrFail();
        $lendItem->refresh();
        $this->assertSame('lend', $borrowOrder->listing_type);
        $this->assertSame(0, Payment::where('material_exchange_id', $borrowOrder->id)->count());
        $this->assertEquals(0.00, (float) $lendItem->quantity);
        $this->assertSame('inactive', $lendItem->getRawOriginal('status'));
        $this->assertFalse($lendItem->isPermanentlySoldOut());

        // Borrower cannot mark returned (403)
        $this->actingAs($borrower)
            ->post(route('business.orders.return', $borrowOrder))
            ->assertStatus(403);

        // Lender confirms returned -> stock restored back to 5 pcs and active on Marketplace!
        $this->actingAs($lender)
            ->post(route('business.orders.return', $borrowOrder))
            ->assertRedirect();

        $lendItem->refresh();
        $borrowOrder->refresh();
        $this->assertEquals(5.00, (float) $lendItem->quantity);
        $this->assertSame('active', $lendItem->getRawOriginal('status'));
        $this->assertSame('returned', $borrowOrder->delivery_status);
        $this->assertNotNull($borrowOrder->borrowed_returned_at);
    }

    public function test_part_7_and_8_delivery_tracking_timeline_verified_reviews_and_authorization(): void
    {
        $seller = $this->createApprovedBusiness('Verified Seller Timber Bhd');
        $buyer = $this->createApprovedBusiness('Verified Buyer Craft Bhd');
        $intruder = $this->createApprovedBusiness('Unauthorized Intruder Corp');

        $item = Item::create([
            'business_id' => $seller->id,
            'name' => 'Reclaimed Meranti Planks',
            'title' => 'Reclaimed Meranti Planks',
            'description' => 'Solid seasoned meranti planks.',
            'category' => 'Timber',
            'quantity' => 10,
            'original_quantity' => 10,
            'unit' => 'pcs',
            'reusability' => 'reusable',
            'listing_type' => 'free',
            'is_free' => true,
            'price' => 0.00,
            'condition' => 'Used',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
            'status' => 'active',
        ]);

        // Buyer claims item -> initial delivery_status = confirmed
        $this->actingAs($buyer)->post(route('business.marketplace.exchange', $item), ['quantity' => 2]);
        $order = MaterialExchange::where('item_id', $item->id)->firstOrFail();

        // Intruder cannot view order, update status, or submit review (403)
        $this->actingAs($intruder)->get(route('business.orders.show', $order))->assertStatus(403);
        $this->actingAs($intruder)->post(route('business.orders.status', $order), ['delivery_status' => 'delivered'])->assertStatus(403);
        $this->actingAs($intruder)->post(route('business.orders.review', $order), ['rating' => 5, 'comment' => 'Fake review'])->assertStatus(403);

        // Buyer CANNOT submit review before delivery/collection!
        $this->actingAs($buyer)->post(route('business.orders.review', $order), [
            'rating' => 5,
            'comment' => 'Too early review',
        ])->assertSessionHasErrors('review');
        $this->assertDatabaseCount('marketplace_review', 0);

        // Seller progresses delivery statuses step-by-step
        $this->actingAs($seller)->post(route('business.orders.status', $order), [
            'delivery_status' => 'preparing',
            'note' => 'Bundling planks at bay 3.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($seller)->post(route('business.orders.status', $order), [
            'delivery_status' => 'in_transit',
            'note' => 'Dispatched via Lalamove lorry.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($seller)->post(route('business.orders.status', $order), [
            'delivery_status' => 'delivered',
            'note' => 'Delivered to buyer loading dock.',
        ])->assertSessionHasNoErrors();

        // Buyer confirms completed
        $this->actingAs($buyer)->post(route('business.orders.status', $order), [
            'delivery_status' => 'completed',
        ])->assertSessionHasNoErrors();

        $this->assertSame('completed', $order->fresh()->delivery_status);
        $this->assertGreaterThanOrEqual(5, OrderStatusHistory::where('material_exchange_id', $order->id)->count());

        // Seller cannot self-review (403)
        $this->actingAs($seller)->post(route('business.orders.review', $order), [
            'rating' => 5,
            'comment' => 'Self review attempt',
        ])->assertStatus(403);

        // Buyer now sees order in My Reviews (eligible) and submits verified 5-star review
        $this->actingAs($buyer)->get(route('business.reviews.index', ['tab' => 'eligible']))
            ->assertOk()
            ->assertSee('Reclaimed Meranti Planks');

        $this->actingAs($buyer)->post(route('business.orders.review', $order), [
            'rating' => 5,
            'comment' => 'High quality timber planks, accurately measured and delivered on time.',
        ])->assertRedirect(route('business.reviews.index', ['tab' => 'submitted']));

        $this->assertDatabaseHas('marketplace_review', [
            'material_exchange_id' => $order->id,
            'reviewer_business_id' => $buyer->id,
            'seller_business_id' => $seller->id,
            'rating' => 5,
        ]);

        // Duplicate review for the same order is rejected
        $this->actingAs($buyer)->post(route('business.orders.review', $order), [
            'rating' => 4,
            'comment' => 'Duplicate attempt',
        ])->assertSessionHasErrors('review');
        $this->assertSame(1, MarketplaceReview::where('material_exchange_id', $order->id)->count());

        // Seller sees received review in My Reviews and buyer sees seller rating on Marketplace
        $this->actingAs($seller)->get(route('business.reviews.index', ['tab' => 'received']))
            ->assertOk()
            ->assertSee('5.0 / 5.0')
            ->assertSee('High quality timber planks');

        $this->actingAs($buyer)->get(route('business.marketplace'))
            ->assertOk()
            ->assertSee('5.0 (1)');
    }

    public function test_part_9_non_reusable_ecocycle_helper_qualitative_impact_and_strict_verified_gps_matching(): void
    {
        $business = $this->createApprovedBusiness('Klang Food Processor Sdn Bhd');

        $spoiledFood = Item::create([
            'business_id' => $business->id,
            'name' => 'Expired Vegetable Trimmings',
            'title' => 'Expired Vegetable Trimmings',
            'description' => 'Spoiled organic kitchen trimmings unsuitable for consumption.',
            'category' => 'Food & Organic Waste',
            'quantity' => 40,
            'original_quantity' => 40,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'listing_type' => 'free',
            'is_free' => true,
            'price' => null,
            'condition' => 'Damaged',
            'status' => 'in_stock',
        ]);

        // Non-reusable item CANNOT be published to Marketplace
        $this->actingAs($business)->post(route('business.inventory.publish', $spoiledFood))
            ->assertSessionHasErrors('reusability');

        // EcoCycle Helper renders exact required heading, description, owner actions, and qualitative impact (ZERO invented kg CO2e!)
        $helperPage = $this->actingAs($business)->get(route('business.helper', ['item_id' => $spoiledFood->id]));
        $helperPage->assertOk()
            ->assertSee('EcoCycle Helper')
            ->assertSee('This material has been classified as non-reusable. EcoCycle Helper provides recommendations on how to manage, recycle or dispose of the material properly.')
            ->assertSee('What Can the Business Owner Do?')
            ->assertSee('Environmental Impact (Qualitative)')
            ->assertSee('Carbon Impact Guidance (Qualitative)')
            ->assertDontSee('kg CO2e');

        // Verified GPS search for Food & Organic Waste returns ONLY verified composting/organic facilities
        $orgGps = $this->actingAs($business)->postJson(route('business.helper.nearby'), [
            'lat' => 3.0738,
            'lng' => 101.5183,
            'category' => 'Food & Organic Waste',
        ]);
        $orgGps->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('verified_match_found', true);
        $this->assertNotEmpty($orgGps->json('centres'));

        // Verified GPS search for an unmatched specialized category MUST NOT recommend unverified/unrelated facilities!
        $noMatchGps = $this->actingAs($business)->postJson(route('business.helper.nearby'), [
            'lat' => 3.0738,
            'lng' => 101.5183,
            'category' => 'Radioactive Medical Isotopes',
        ]);
        $noMatchGps->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('verified_match_found', false)
            ->assertJsonCount(0, 'centres');
        $this->assertNotEmpty($noMatchGps->json('alternative_recommendations'));
    }

    public function test_material_analysis_directs_to_update_inventory_details_before_saving_and_supports_cancel(): void
    {
        $seller = $this->createApprovedBusiness('DraftFlow Timber Sdn Bhd', 'No. 8, Jalan Kilang, Klang');
        $photo = UploadedFile::fake()->image('draft_pallets.jpg', 640, 480);

        // 1. Analyze Item returns update_details_url and does NOT save to `item` table yet
        $analyzeResp = $this->actingAs($seller)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Grade-A Heat Treated Pallets',
            'description' => 'Clean wooden pallets ready for logistics reuse.',
            'quantity' => 12,
            'unit' => 'pcs',
            'condition' => 'Used',
            'image_1' => $photo,
        ]);

        $analyzeResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('update_details_url', route('business.inventory.prepare'));

        $this->assertSame(0, Item::count());

        // 2. Central Update Inventory Details page shows Material Analysis (zero Gemini/API wording) and pre-filled fields
        $this->actingAs($seller)
            ->get(route('business.inventory.prepare'))
            ->assertOk()
            ->assertSee('Update Inventory Details')
            ->assertSee('Material Analysis')
            ->assertSee('Grade-A Heat Treated Pallets')
            ->assertSee('Save Item')
            ->assertSee('Cancel')
            ->assertDontSee('Google Gemini')
            ->assertDontSee('Gemini AI');

        // 3. Clicking Cancel discards draft without creating any item row
        $this->actingAs($seller)
            ->post(route('business.inventory.draft.cancel'))
            ->assertRedirect(route('business.inventory'));

        $this->assertSame(0, Item::count());
        $this->assertNull(session('inventory_pending_draft'));
    }

    public function test_update_inventory_details_save_item_workflow_for_reusable_and_non_reusable(): void
    {
        $seller = $this->createApprovedBusiness('CentralWorkflow Industries Bhd', 'Lot 15, Shah Alam');
        $viewer = $this->createApprovedBusiness('Marketplace Viewer Sdn Bhd');

        // A. Reusable path: Analyze -> Update Inventory Details -> Save Item -> saved (not published) -> Publish to Marketplace
        $reusPhoto = UploadedFile::fake()->image('reusable_bins.jpg', 600, 400);
        $reusAnalyze = $this->actingAs($seller)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Heavy-Duty Stackable Plastic Bins',
            'description' => 'Clean undamaged industrial storage bins.',
            'quantity' => 15,
            'unit' => 'pcs',
            'condition' => 'New',
            'image_1' => $reusPhoto,
        ])->assertOk()->json();

        // Sell requires Price per Unit > 0 on Save Item from Update Inventory Details
        $this->actingAs($seller)
            ->from(route('business.inventory.prepare'))
            ->post(route('business.inventory.store'), [
                'from_prepare' => 1,
                'name' => 'Heavy-Duty Stackable Plastic Bins',
                'description' => 'Clean undamaged industrial storage bins.',
                'category' => 'Plastics & Polymers',
                'quantity' => 15,
                'unit' => 'pcs',
                'condition' => 'New',
                'reusability' => 'reusable',
                'listing_type' => 'buy',
                'price' => '',
                'saved_image_slots' => json_encode($reusAnalyze['saved_image_slots']),
                'analysis_token' => $reusAnalyze['analysis_token'],
                'ai_analysis_json' => json_encode($reusAnalyze['analysis']),
            ])->assertSessionHasErrors('price');

        $this->assertSame(0, Item::count());

        // Provide valid Price per Unit RM 22.00 and click Save Item
        $saveReusResp = $this->actingAs($seller)->post(route('business.inventory.store'), [
            'from_prepare' => 1,
            'name' => 'Heavy-Duty Stackable Plastic Bins',
            'description' => 'Clean undamaged industrial storage bins.',
            'category' => 'Plastics & Polymers',
            'quantity' => 15,
            'unit' => 'pcs',
            'condition' => 'New',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 22.00,
            'pickup_address' => 'Lot 15, Shah Alam, Selangor',
            'saved_image_slots' => json_encode($reusAnalyze['saved_image_slots']),
            'analysis_token' => $reusAnalyze['analysis_token'],
            'ai_analysis_json' => json_encode($reusAnalyze['analysis']),
        ]);

        $reusItem = InventoryItem::where('name', 'Heavy-Duty Stackable Plastic Bins')->firstOrFail();
        $saveReusResp->assertRedirect(route('business.inventory.edit', $reusItem));
        $this->assertFalse($reusItem->isPublishedToMarketplace());

        // Not visible in Marketplace until explicitly published
        $this->flushSession();
        $this->actingAs($viewer)->get(route('business.marketplace'))
            ->assertOk()
            ->assertViewHas('listings', fn ($listings): bool => ! $listings->contains('id', $reusItem->id))
            ->assertDontSee('Heavy-Duty Stackable Plastic Bins');

        // Explicitly publish -> now visible in Marketplace
        $this->actingAs($seller)->post(route('business.inventory.publish', $reusItem))
            ->assertRedirect(route('business.marketplace'));

        $this->actingAs($viewer)->get(route('business.marketplace'))
            ->assertOk()
            ->assertSee('Heavy-Duty Stackable Plastic Bins');

        // B. Non-Reusable path: Analyze -> Update Inventory Details -> Save Item -> saved & automatically forwarded to EcoCycle Helper
        $scrapPhoto = UploadedFile::fake()->image('spoiled_waste.jpg', 600, 400);
        $scrapAnalyze = $this->actingAs($seller)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Contaminated Kitchen Food Waste',
            'description' => 'Spoiled organic scraps unsuitable for reuse.',
            'quantity' => 30,
            'unit' => 'kg',
            'condition' => 'Damaged',
            'image_1' => $scrapPhoto,
        ])->assertOk()->json();

        $saveScrapResp = $this->actingAs($seller)->post(route('business.inventory.store'), [
            'from_prepare' => 1,
            'name' => 'Contaminated Kitchen Food Waste',
            'description' => 'Spoiled organic scraps unsuitable for reuse.',
            'category' => 'Food & Organic Waste',
            'quantity' => 30,
            'unit' => 'kg',
            'condition' => 'Damaged',
            'reusability' => 'not_reusable',
            'saved_image_slots' => json_encode($scrapAnalyze['saved_image_slots']),
            'analysis_token' => $scrapAnalyze['analysis_token'],
            'ai_analysis_json' => json_encode($scrapAnalyze['analysis']),
        ]);

        $scrapItem = InventoryItem::where('name', 'Contaminated Kitchen Food Waste')->firstOrFail();
        $saveScrapResp->assertRedirect(route('business.helper', ['item_id' => $scrapItem->id]));
        $this->assertFalse($scrapItem->isPublishedToMarketplace());
    }
}
