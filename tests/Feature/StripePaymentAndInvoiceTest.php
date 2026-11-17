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
            'unit' => 'units',
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
        $this->assertSame('pending_payment', $exchange->payment_status);
        $this->assertDatabaseCount('invoice', 0);

        // 2. Simple Payment page shows Item, Type, Total, Status, and Pay Now
        $this->actingAs($buyer)
            ->get(route('business.orders.checkout', $exchange))
            ->assertOk()
            ->assertSee('Industrial HDPE Pallets')
            ->assertSee('Sell')
            ->assertSee('RM 150.00')
            ->assertSee('Pending')
            ->assertSee('Pay Now');

        // 3. Buyer clicks Pay Now -> creates Stripe Checkout Session with MYR 15000 sen
        $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $exchange))
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_a1b2c3d4e5');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
                && $request['line_items[0][price_data][currency]'] === 'myr'
                && $request['line_items[0][price_data][unit_amount]'] === '15000'
                && ! empty($request->header('Idempotency-Key'));
        });

        $payment = Payment::where('material_exchange_id', $exchange->id)->firstOrFail();

        // 4. Success redirect ALONE must NOT mark order as paid or generate receipt
        $this->actingAs($buyer)
            ->get(route('business.payments.stripe.success', ['session_id' => 'cs_test_a1b2c3d4e5']))
            ->assertRedirect(route('business.invoices.index', ['tab' => 'orders']));

        $exchange->refresh();
        $this->assertSame('pending_payment', $exchange->payment_status);
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
                    'payment_method_types' => ['card'],
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

        // 6. Verified Stripe webhook marks Paid, deducts stock ONCE, and generates EcoCycle Receipt
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
        $item->refresh();
        $this->assertSame('paid', $exchange->payment_status);
        $this->assertSame('completed', $exchange->status);
        $this->assertEquals(15.00, (float) $item->quantity);

        $receipt = Invoice::where('material_exchange_id', $exchange->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^ECO-RCP-\d{4}-00001$/', $receipt->invoice_number);
        $this->assertEquals(150.00, (float) $receipt->total_amount);
        $this->assertSame('paid', $receipt->payment_status);
        $this->assertSame($pickupAddress, $receipt->pickup_address);

        // 7. Duplicate webhook is idempotent
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
        $this->assertDatabaseCount('invoice', 1);

        // 8. Duplicate payment attempt is blocked once paid
        $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $exchange))
            ->assertRedirect(route('business.invoices.show', $receipt));
        Http::assertSentCount(1);

        // 9. Simple EcoCycle Receipt HTML and PDF contain essential info + actual pickup address
        $this->actingAs($buyer)
            ->get(route('business.invoices.show', $receipt))
            ->assertOk()
            ->assertSee('EcoCycle Receipt')
            ->assertSee($receipt->invoice_number)
            ->assertSee('Industrial HDPE Pallets')
            ->assertSee('5 units')
            ->assertSee('RM 150.00')
            ->assertSee('GreenMetal Sdn Bhd')
            ->assertSee('Klang Packaging Bhd')
            ->assertSee('Paid')
            ->assertSee($pickupAddress);

        $pdfResp = $this->actingAs($buyer)->get(route('business.invoices.pdf', $receipt));
        $pdfResp->assertOk();
        $pdfResp->assertHeader('Content-Type', 'application/pdf');
        $pdfBody = $pdfResp->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdfBody);
        $this->assertStringContainsString('%%EOF', $pdfBody);
        $this->assertStringContainsString('EcoCycle Receipt', $pdfBody);
        $this->assertStringContainsString($receipt->invoice_number, $pdfBody);
        $this->assertStringContainsString('RM 150.00', $pdfBody);
        $this->assertStringContainsString('Lot 42, Kawasan Perindustrian Bukit Raja', $pdfBody);

        // Seller can also view and download the receipt
        $this->actingAs($seller)->get(route('business.invoices.show', $receipt))->assertOk();
        $this->actingAs($seller)->get(route('business.invoices.pdf', $receipt))->assertOk();

        // 10. Simple Transaction History shows required columns (Item, Type, Total, Status, Date)
        $this->actingAs($buyer)
            ->get(route('business.invoices.index'))
            ->assertOk()
            ->assertSee('Item')
            ->assertSee('Type')
            ->assertSee('Total')
            ->assertSee('Status')
            ->assertSee('Date')
            ->assertSee('Industrial HDPE Pallets')
            ->assertSee('Sell')
            ->assertSee('RM 150.00')
            ->assertSee('Paid')
            ->assertSee('View Receipt')
            ->assertSee('Download PDF');
    }

    public function test_failed_payment_and_cancelled_checkout_do_not_mark_paid_or_create_receipt(): void
    {
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_cancel_001',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_cancel_001',
                'payment_intent' => 'pi_test_fail_001',
            ], 200),
        ]);

        $seller = $this->createApprovedBusiness('Seller Co');
        $buyer = $this->createApprovedBusiness('Buyer Co');
        $item = $this->createReusableItem($seller, ['price' => 30.00, 'quantity' => 10]);

        $this->actingAs($buyer)->post(route('business.marketplace.exchange', $item), ['quantity' => 2]);
        $exchange = MaterialExchange::firstOrFail();

        $this->actingAs($buyer)->post(route('business.payments.stripe.checkout', $exchange));
        $payment = Payment::firstOrFail();

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
                    'id' => 'pi_test_fail_001',
                    'metadata' => [
                        'exchange_id' => (string) $exchange->id,
                        'payment_id' => (string) $payment->id,
                    ],
                    'last_payment_error' => [
                        'message' => 'Your card has insufficient funds.',
                    ],
                ],
            ],
        ]);
        $sig = StripePaymentService::buildTestSignatureHeader($failPayload, $this->webhookSecret);

        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'],
            $failPayload
        )->assertOk()->assertJsonPath('status', 'failed');

        $exchange->refresh();
        $payment->refresh();
        $item->refresh();
        $this->assertSame('failed', $exchange->payment_status);
        $this->assertSame('failed', $payment->status);
        $this->assertEquals(10.00, (float) $item->quantity);
        $this->assertDatabaseCount('invoice', 0);
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

        // Simple Free receipt generated automatically without Stripe
        $receipt = Invoice::where('material_exchange_id', $freeOrder->id)->firstOrFail();
        $this->assertSame('free', $receipt->payment_status);
        $this->assertEquals(0.00, (float) $receipt->total_amount);
        $this->assertSame($pickupAddress, $receipt->pickup_address);

        $this->actingAs($buyer)
            ->get(route('business.invoices.show', $receipt))
            ->assertOk()
            ->assertSee('EcoCycle Receipt')
            ->assertSee('RM 0.00')
            ->assertSee('Free')
            ->assertSee($pickupAddress);

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

    public function test_exchange_borrow_lend_removed_and_non_reusable_cannot_be_purchased(): void
    {
        Http::fake();

        $seller = $this->createApprovedBusiness('Rules Seller Bhd');
        $buyer = $this->createApprovedBusiness('Rules Buyer Bhd');

        // 1. Marketplace creation rejects borrow, lend, exchange, barter
        foreach (['borrow', 'lend', 'exchange', 'barter'] as $invalidType) {
            $this->actingAs($seller)
                ->post(route('business.marketplace.store'), [
                    'title' => "Invalid {$invalidType} Listing",
                    'category' => 'Plastics & Polymers',
                    'reusability' => 'reusable',
                    'listing_type' => $invalidType,
                    'condition' => 'Used',
                    'quantity' => 10,
                    'unit' => 'units',
                    'price' => 10.00,
                ])
                ->assertSessionHasErrors('listing_type');
        }

        // 2. Legacy borrow/exchange listing cannot be ordered (422)
        $legacyBorrowItem = $this->createReusableItem($seller, [
            'title' => 'Legacy Borrow Item',
            'name' => 'Legacy Borrow Item',
            'listing_type' => 'borrow',
            'price' => 25.00,
        ]);

        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $legacyBorrowItem), ['quantity' => 2])
            ->assertStatus(422);

        // 3. Non-Reusable item cannot be purchased (400)
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

        // 4. UI does not display Borrow / Lend options
        $this->actingAs($buyer)
            ->get(route('business.marketplace'))
            ->assertOk()
            ->assertDontSee('Borrow (Loan)')
            ->assertDontSee('Request Material Exchange');

        $this->actingAs($seller)
            ->get(route('business.listings'))
            ->assertOk()
            ->assertDontSee('Borrowed Materials');

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
            'invoice_number' => 'ECO-RCP-2026-00001',
            'item_id' => $item->id,
            'provider_business_id' => $seller->id,
            'requester_business_id' => $buyer->id,
            'listing_type' => 'buy',
            'quantity' => 2,
            'unit' => 'units',
            'unit_price' => 100.00,
            'total_price' => 200.00,
            'status' => 'completed',
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        $receipt = Invoice::create([
            'invoice_number' => 'ECO-RCP-2026-00001',
            'material_exchange_id' => $exchange->id,
            'seller_business_id' => $seller->id,
            'buyer_business_id' => $buyer->id,
            'order_reference' => 'ECO-ORD-2026-SEC01',
            'currency' => 'myr',
            'item_name' => $item->name,
            'quantity' => 2,
            'unit' => 'units',
            'unit_price' => 100.00,
            'subtotal' => 200.00,
            'tax_amount' => 0.00,
            'total_amount' => 200.00,
            'payment_status' => 'paid',
            'payment_method' => 'card',
            'payment_reference' => 'pi_sec_001',
            'pickup_address' => $item->pickup_address,
            'issued_at' => now(),
            'paid_at' => now(),
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
            ->assertDontSee('ECO-RCP-2026-00001');
    }
}
