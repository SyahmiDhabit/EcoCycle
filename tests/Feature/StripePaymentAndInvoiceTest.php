<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\MarketplaceListing;
use App\Models\MaterialExchange;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripePaymentAndInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_ecocycle_signing_secret_12345';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.stripe.key', 'pk_test_fake_publishable');
        config()->set('services.stripe.secret', 'sk_test_fake_secret');
        config()->set('services.stripe.webhook_secret', $this->webhookSecret);
        config()->set('services.stripe.currency', 'myr');
    }

    private function createApprovedBusiness(string $name, ?string $regNo = null): User
    {
        return User::factory()->create([
            'business_name' => $name,
            'business_registration_number' => $regNo ?? fake()->unique()->numerify('202401######'),
            'account_status' => 'approved',
            'registered_address' => 'No. 18, Jalan Industri 2',
            'city' => 'Shah Alam',
            'postcode' => '40200',
            'state' => 'Selangor',
            'business_phone' => '03-55101234',
        ]);
    }

    private function createReusableItem(User $seller, array $overrides = []): MarketplaceListing
    {
        return MarketplaceListing::create(array_merge([
            'business_id' => $seller->id,
            'title' => 'Industrial HDPE Pallets',
            'name' => 'Industrial HDPE Pallets',
            'description' => 'Clean heavy-duty reusable pallets stored indoors.',
            'category' => 'Plastics & Polymers',
            'quantity' => 50,
            'unit' => 'pcs',
            'price' => 30.00,
            'is_free' => false,
            'listing_type' => 'buy',
            'reusability' => 'reusable',
            'condition' => 'Used',
            'location_city' => 'Klang',
            'location_state' => 'Selangor',
            'pickup_address' => 'Lot 42, Kawasan Perindustrian Bukit Raja, 41050 Klang, Selangor',
            'status' => 'active',
        ], $overrides));
    }

    public function test_sell_item_stripe_payment_webhook_verification_and_simple_pdf_receipt(): void
    {
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_a1b2c3d4e5',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_a1b2c3d4e5',
                'payment_intent' => 'pi_test_99887766',
            ], 200),
        ]);

        $seller = $this->createApprovedBusiness('GreenMetal Sdn Bhd');
        $buyer = $this->createApprovedBusiness('Klang Packaging Bhd');
        $pickupAddress = 'Lot 42, Kawasan Perindustrian Bukit Raja, 41050 Klang, Selangor';
        $item = $this->createReusableItem($seller, [
            'price' => 30.00,
            'quantity' => 20,
            'pickup_address' => $pickupAddress,
        ]);

        // 1. Buyer places Sell order (client tampered total_price=1.00 is ignored; server computes 5 * 30.00 = 150.00)
        $orderResp = $this->actingAs($buyer)->post(route('business.marketplace.exchange', $item), [
            'quantity' => 5,
            'total_price' => 1.00,
            'proceed_to_checkout' => 1,
        ]);

        $exchange = MaterialExchange::where('item_id', $item->id)->firstOrFail();
        $orderResp->assertRedirect(route('business.orders.checkout', $exchange));
        $this->assertEquals(150.00, (float) $exchange->total_price);
        $this->assertEquals(30.00, (float) $exchange->unit_price);
        $this->assertSame('pending', $exchange->payment_status);
        $this->assertDatabaseCount('payment', 1);
        $this->assertDatabaseCount('invoice', 0);

        // 2. Simple Payment page shows minimal required fields: Item Name, Quantity, Type, Total Amount (RM), Payment Status
        $this->actingAs($buyer)
            ->get(route('business.orders.checkout', $exchange))
            ->assertOk()
            ->assertSee('Item Name')
            ->assertSee('Industrial HDPE Pallets')
            ->assertSee('Quantity')
            ->assertSee('5 pcs')
            ->assertSee('Sell')
            ->assertSee('RM 150.00')
            ->assertSee('Pending')
            ->assertSee('Pay Now');

        // 3. Buyer clicks Pay Now -> creates Stripe Checkout Session with MYR 15000 sen & reuses single payment row
        $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $exchange))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_a1b2c3d4e5');

        $this->assertDatabaseCount('payment', 1);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
                && $request['line_items[0][price_data][currency]'] === 'myr'
                && $request['line_items[0][price_data][unit_amount]'] === '15000'
                && ! empty($request->header('Idempotency-Key'));
        });

        $payment = Payment::where('material_exchange_id', $exchange->id)->firstOrFail();
        $this->assertSame('cs_test_a1b2c3d4e5', $payment->stripe_checkout_session_id);
        $this->assertSame('myr', $payment->currency);

        // 4. Success redirect ALONE must NOT mark order as paid or generate receipt
        $this->actingAs($buyer)
            ->get(route('business.payments.stripe.success', ['session_id' => 'cs_test_a1b2c3d4e5']))
            ->assertRedirect(route('business.invoices.index', ['tab' => 'orders']));

        $exchange->refresh();
        $this->assertSame('pending', $exchange->payment_status);
        $this->assertDatabaseCount('invoice', 0);

        // 5. Invalid webhook signature is rejected (400)
        $webhookPayload = json_encode([
            'id' => 'evt_test_paid_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_a1b2c3d4e5',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_99887766',
                    'currency' => 'myr',
                    'amount_total' => 15000,
                    'client_reference_id' => (string) $exchange->id,
                    'metadata' => [
                        'exchange_id' => (string) $exchange->id,
                        'payment_id' => (string) $payment->id,
                    ],
                ],
            ],
        ]);

        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=invalid_signature', 'CONTENT_TYPE' => 'application/json'],
            $webhookPayload
        )->assertStatus(400);

        // 6. Verified Stripe webhook marks Paid, deducts stock ONCE, and generates EcoCycle Receipt (ECO-YYYY-00001)
        $validSig = StripePaymentService::buildTestSignatureHeader($webhookPayload, $this->webhookSecret);
        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $validSig, 'CONTENT_TYPE' => 'application/json'],
            $webhookPayload
        )->assertOk()->assertJsonPath('status', 'paid');

        $exchange->refresh();
        $payment->refresh();
        $item->refresh();
        $this->assertSame('paid', $exchange->payment_status);
        $this->assertSame('completed', $exchange->status);
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals(15.00, (float) $item->quantity);

        $receipt = Invoice::where('material_exchange_id', $exchange->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^ECO-\d{4}-00001$/', $receipt->invoice_number);
        $this->assertEquals(150.00, (float) $receipt->total_amount);
        $this->assertSame('paid', $receipt->payment_status);
        $this->assertSame('Klang Packaging Bhd', $receipt->buyer_business_name);
        $this->assertSame('GreenMetal Sdn Bhd', $receipt->seller_business_name);
        $this->assertSame($pickupAddress, $receipt->pickup_address);
        $this->assertDatabaseCount('payment', 1);
        $this->assertDatabaseCount('invoice', 1);

        // 7. Duplicate webhook is idempotent (stock NOT deducted again, no duplicate payment/receipt)
        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $validSig, 'CONTENT_TYPE' => 'application/json'],
            $webhookPayload
        )->assertOk()->assertJsonPath('status', 'duplicate_event_ignored');
        $this->assertEquals(15.00, (float) $item->fresh()->quantity);
        $this->assertDatabaseCount('payment', 1);
        $this->assertDatabaseCount('invoice', 1);

        // 8. Duplicate payment attempt is blocked once paid
        $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $exchange))
            ->assertRedirect(route('business.invoices.show', $receipt));
        Http::assertSentCount(1);

        // 9. Simple EcoCycle Receipt HTML and PDF contain exact 10 elements
        $this->actingAs($buyer)
            ->get(route('business.invoices.show', $receipt))
            ->assertOk()
            ->assertSee('EcoCycle Receipt')
            ->assertSee($receipt->invoice_number)
            ->assertSee('Transaction Date')
            ->assertSee('Buyer Business Name')
            ->assertSee('Klang Packaging Bhd')
            ->assertSee('Seller Business Name')
            ->assertSee('GreenMetal Sdn Bhd')
            ->assertSee('Industrial HDPE Pallets')
            ->assertSee('5 pcs')
            ->assertSee('RM 150.00')
            ->assertSee($pickupAddress)
            ->assertSee('Paid');

        $pdfResp = $this->actingAs($buyer)->get(route('business.invoices.pdf', $receipt));
        $pdfResp->assertOk();
        $pdfResp->assertHeader('Content-Type', 'application/pdf');
        $pdfBody = $pdfResp->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdfBody);
        $this->assertStringContainsString('%%EOF', $pdfBody);
        $this->assertStringContainsString('EcoCycle Receipt', $pdfBody);
        $this->assertStringContainsString($receipt->invoice_number, $pdfBody);
        $this->assertStringContainsString('Klang Packaging Bhd', $pdfBody);
        $this->assertStringContainsString('GreenMetal Sdn Bhd', $pdfBody);
        $this->assertStringContainsString('5 pcs', $pdfBody);
        $this->assertStringContainsString('RM 150.00', $pdfBody);
        $this->assertStringContainsString('Lot 42, Kawasan Perindustrian Bukit Raja', $pdfBody);

        // Seller can also view and download the receipt
        $this->actingAs($seller)->get(route('business.invoices.show', $receipt))->assertOk();
        $this->actingAs($seller)->get(route('business.invoices.pdf', $receipt))->assertOk();

        // 10. Historical receipt remains UNCHANGED even after seller edits and restocks inventory
        $item->update([
            'name' => 'Renamed Polymer Crates V2',
            'title' => 'Renamed Polymer Crates V2',
            'quantity' => 500,
            'price' => 99.00,
            'pickup_address' => 'New Warehouse Address, Penang',
        ]);

        $receipt->refresh();
        $this->assertSame('Industrial HDPE Pallets', $receipt->item_name);
        $this->assertEquals(5.00, (float) $receipt->quantity);
        $this->assertEquals(150.00, (float) $receipt->total_amount);
        $this->assertSame($pickupAddress, $receipt->pickup_address);
    }

    public function test_payment_retries_reuse_single_payment_row_and_support_cancelled_failed_and_refunded(): void
    {
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::sequence()
                ->push([
                    'id' => 'cs_test_attempt_001',
                    'url' => 'https://checkout.stripe.com/c/pay/cs_test_attempt_001',
                    'payment_intent' => 'pi_test_attempt_001',
                ], 200)
                ->push([
                    'id' => 'cs_test_attempt_002',
                    'url' => 'https://checkout.stripe.com/c/pay/cs_test_attempt_002',
                    'payment_intent' => 'pi_test_attempt_002',
                ], 200),
        ]);

        $seller = $this->createApprovedBusiness('Seller Co');
        $buyer = $this->createApprovedBusiness('Buyer Co');
        $item = $this->createReusableItem($seller, ['price' => 30.00, 'quantity' => 10]);

        $this->actingAs($buyer)->post(route('business.marketplace.exchange', $item), ['quantity' => 2]);
        $exchange = MaterialExchange::firstOrFail();

        // Attempt 1
        $this->actingAs($buyer)->post(route('business.payments.stripe.checkout', $exchange));
        $payment = Payment::firstOrFail();
        $this->assertDatabaseCount('payment', 1);

        // 1. Cancelled checkout redirect
        $this->actingAs($buyer)
            ->get(route('business.payments.stripe.cancel', $exchange))
            ->assertRedirect(route('business.orders.checkout', $exchange));

        $exchange->refresh();
        $payment->refresh();
        $this->assertSame('cancelled', $exchange->payment_status);
        $this->assertSame('cancelled', $payment->status);
        $this->assertDatabaseCount('invoice', 0);

        // 2. Failed payment webhook
        $failPayload = json_encode([
            'id' => 'evt_test_fail_001',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_attempt_001',
                    'metadata' => [
                        'exchange_id' => (string) $exchange->id,
                        'payment_id' => (string) $payment->id,
                    ],
                ],
            ],
        ]);
        $sigFail = StripePaymentService::buildTestSignatureHeader($failPayload, $this->webhookSecret);

        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $sigFail, 'CONTENT_TYPE' => 'application/json'],
            $failPayload
        )->assertOk()->assertJsonPath('status', 'failed');

        $this->assertSame('failed', $exchange->fresh()->payment_status);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertEquals(10.00, (float) $item->fresh()->quantity);
        $this->assertDatabaseCount('invoice', 0);

        // 3. Retry payment -> MUST reuse the same single Payment row (no duplicate payment rows!)
        $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $exchange))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_attempt_002');

        $this->assertDatabaseCount('payment', 1);
        $payment->refresh();
        $this->assertSame('cs_test_attempt_002', $payment->stripe_checkout_session_id);
        $this->assertSame('pending', $payment->status);

        // 4. Paid webhook on retry succeeds and deducts stock once
        $paidPayload = json_encode([
            'id' => 'evt_test_paid_retry_002',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_attempt_002',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_attempt_002',
                    'currency' => 'myr',
                    'amount_total' => 6000,
                    'metadata' => [
                        'exchange_id' => (string) $exchange->id,
                        'payment_id' => (string) $payment->id,
                    ],
                ],
            ],
        ]);
        $sigPaid = StripePaymentService::buildTestSignatureHeader($paidPayload, $this->webhookSecret);
        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $sigPaid, 'CONTENT_TYPE' => 'application/json'],
            $paidPayload
        )->assertOk()->assertJsonPath('status', 'paid');

        $this->assertEquals(8.00, (float) $item->fresh()->quantity);
        $this->assertDatabaseCount('invoice', 1);

        // 5. Refund webhook updates Payment, Order, and Receipt to Refunded
        $refundPayload = json_encode([
            'id' => 'evt_test_refund_003',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_test_003',
                    'payment_intent' => 'pi_test_attempt_002',
                    'metadata' => [
                        'exchange_id' => (string) $exchange->id,
                        'payment_id' => (string) $payment->id,
                    ],
                ],
            ],
        ]);
        $sigRefund = StripePaymentService::buildTestSignatureHeader($refundPayload, $this->webhookSecret);
        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $sigRefund, 'CONTENT_TYPE' => 'application/json'],
            $refundPayload
        )->assertOk()->assertJsonPath('status', 'refunded');

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('refunded', $exchange->fresh()->payment_status);
        $this->assertSame('refunded', Invoice::firstOrFail()->payment_status);
    }

    public function test_free_item_generates_free_receipt_with_rm_zero_without_stripe(): void
    {
        Http::fake();

        $seller = $this->createApprovedBusiness('Free Supplier Sdn Bhd');
        $buyer = $this->createApprovedBusiness('Free Receiver Sdn Bhd');
        $pickupAddress = 'Warehouse 7, Taman Perindustrian Subang, 47500 Subang Jaya, Selangor';

        $freeItem = $this->createReusableItem($seller, [
            'title' => 'Clean Corrugated Boxes',
            'name' => 'Clean Corrugated Boxes',
            'listing_type' => 'free',
            'is_free' => true,
            'price' => 0.00,
            'quantity' => 20,
            'unit' => 'Box',
            'pickup_address' => $pickupAddress,
        ]);

        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $freeItem), [
                'quantity' => 4,
                'proceed_to_checkout' => 1,
            ])
            ->assertRedirect(route('business.transactions', ['tab' => 'purchases']));

        // Zero Stripe calls made
        Http::assertNothingSent();

        $freeOrder = MaterialExchange::where('item_id', $freeItem->id)->firstOrFail();
        $this->assertEquals(0.00, (float) $freeOrder->total_price);
        $this->assertSame('free', $freeOrder->payment_status);
        $this->assertSame('completed', $freeOrder->status);
        $this->assertEquals(16.00, (float) $freeItem->fresh()->quantity);

        // Single Free Payment record stored with RM 0.00 and no Stripe IDs
        $payment = Payment::where('material_exchange_id', $freeOrder->id)->firstOrFail();
        $this->assertEquals(0.00, (float) $payment->amount);
        $this->assertSame('free', $payment->status);
        $this->assertNull($payment->stripe_checkout_session_id);
        $this->assertNull($payment->stripe_payment_intent_id);

        // Simple Free receipt generated automatically without Stripe
        $receipt = Invoice::where('material_exchange_id', $freeOrder->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^ECO-\d{4}-00001$/', $receipt->invoice_number);
        $this->assertSame('free', $receipt->payment_status);
        $this->assertEquals(0.00, (float) $receipt->total_amount);
        $this->assertSame('4 Box', $receipt->formattedQuantity());
        $this->assertSame($pickupAddress, $receipt->pickup_address);

        // Both buyer and seller can view and download Free receipt PDF
        $this->actingAs($buyer)
            ->get(route('business.invoices.show', $receipt))
            ->assertOk()
            ->assertSee('EcoCycle Receipt')
            ->assertSee('RM 0.00')
            ->assertSee('Free')
            ->assertSee($pickupAddress);

        $this->actingAs($seller)
            ->get(route('business.invoices.show', $receipt))
            ->assertOk()
            ->assertSee($receipt->invoice_number);

        $pdfResp = $this->actingAs($buyer)->get(route('business.invoices.pdf', $receipt));
        $pdfResp->assertOk();
        $pdfResp->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('RM 0.00', $pdfResp->getContent());
        $this->assertStringContainsString('Free', $pdfResp->getContent());

        // Attempting Stripe Checkout on Free order does not call Stripe
        $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $freeOrder))
            ->assertSessionHasErrors('payment');
        Http::assertNothingSent();
    }

    public function test_overselling_is_prevented_before_checkout_and_on_order(): void
    {
        Http::fake();

        $seller = $this->createApprovedBusiness('Stock Seller Bhd');
        $buyer = $this->createApprovedBusiness('Stock Buyer Bhd');
        $item = $this->createReusableItem($seller, ['quantity' => 5, 'price' => 20.00]);

        // 1. Cannot order more than available stock
        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $item), ['quantity' => 10])
            ->assertSessionHasErrors('quantity');

        // 2. Order 4 units, then seller depletes stock to 2 before checkout -> Stripe Checkout blocked
        $this->actingAs($buyer)->post(route('business.marketplace.exchange', $item), ['quantity' => 4]);
        $exchange = MaterialExchange::firstOrFail();

        $item->update(['quantity' => 2]);

        $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $exchange))
            ->assertSessionHasErrors('payment');

        Http::assertNothingSent();
    }

    public function test_exchange_borrow_lend_removed_and_non_reusable_cannot_be_purchased(): void
    {
        Http::fake();

        $seller = $this->createApprovedBusiness('Rules Seller Bhd');
        $buyer = $this->createApprovedBusiness('Rules Buyer Bhd');

        foreach (['borrow', 'lend', 'exchange', 'barter'] as $invalidType) {
            $this->actingAs($seller)
                ->post(route('business.marketplace.store'), [
                    'title' => "Invalid {$invalidType} Listing",
                    'category' => 'Plastics & Polymers',
                    'reusability' => 'reusable',
                    'listing_type' => $invalidType,
                    'condition' => 'Used',
                    'quantity' => 10,
                    'unit' => 'pcs',
                    'price' => 10.00,
                ])
                ->assertSessionHasErrors('listing_type');
        }

        $legacyBorrowItem = $this->createReusableItem($seller, [
            'title' => 'Legacy Borrow Item',
            'name' => 'Legacy Borrow Item',
            'listing_type' => 'borrow',
            'price' => 25.00,
        ]);

        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $legacyBorrowItem), ['quantity' => 2])
            ->assertStatus(422);

        $nonReusableItem = $this->createReusableItem($seller, [
            'title' => 'Contaminated Oil Sludge',
            'name' => 'Contaminated Oil Sludge',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 40.00,
        ]);

        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $nonReusableItem), ['quantity' => 2])
            ->assertStatus(400);

        Http::assertNothingSent();
    }

    public function test_unrelated_business_cannot_access_other_business_transaction_or_receipt(): void
    {
        $seller = $this->createApprovedBusiness('Alpha Supply Sdn Bhd');
        $buyer = $this->createApprovedBusiness('Beta Builders Sdn Bhd');
        $intruder = $this->createApprovedBusiness('Intruder Corp Sdn Bhd');
        $item = $this->createReusableItem($seller, ['price' => 100.00]);

        $exchange = MaterialExchange::create([
            'order_reference' => 'ECO-ORD-2026-SEC01',
            'invoice_number' => 'ECO-2026-00001',
            'item_id' => $item->id,
            'provider_business_id' => $seller->id,
            'requester_business_id' => $buyer->id,
            'listing_type' => 'buy',
            'quantity' => 2,
            'unit' => 'pcs',
            'unit_price' => 100.00,
            'total_price' => 200.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        $receipt = Invoice::create([
            'invoice_number' => 'ECO-2026-00001',
            'material_exchange_id' => $exchange->id,
            'seller_business_id' => $seller->id,
            'buyer_business_id' => $buyer->id,
            'buyer_business_name' => $buyer->business_name,
            'seller_business_name' => $seller->business_name,
            'item_name' => $item->name,
            'quantity' => 2,
            'unit' => 'pcs',
            'total_amount' => 200.00,
            'currency' => 'myr',
            'payment_status' => 'paid',
            'pickup_address' => $item->pickup_address,
            'issued_at' => now(),
        ]);

        // Intruder blocked from receipt HTML, PDF download, and checkout
        $this->actingAs($intruder)->get(route('business.invoices.show', $receipt))->assertForbidden();
        $this->actingAs($intruder)->get(route('business.invoices.pdf', $receipt))->assertForbidden();
        $this->actingAs($intruder)->get(route('business.orders.checkout', $exchange))->assertForbidden();
        $this->actingAs($intruder)->post(route('business.payments.stripe.checkout', $exchange))->assertForbidden();

        // Intruder's transaction history must not leak buyer/seller's order
        $this->actingAs($intruder)
            ->get(route('business.invoices.index'))
            ->assertOk()
            ->assertDontSee('Industrial HDPE Pallets')
            ->assertDontSee('ECO-2026-00001');
    }
}
