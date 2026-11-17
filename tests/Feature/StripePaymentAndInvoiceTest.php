<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Item;
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
        config()->set('services.stripe.tax_rate', null);
        config()->set('services.stripe.tax_label', null);
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
            'price' => 50.00,
            'is_free' => false,
            'listing_type' => 'buy',
            'reusability' => 'reusable',
            'condition' => 'Used',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
            'status' => 'active',
        ], $overrides));
    }

    public function test_complete_stripe_payment_webhook_verification_and_pdf_invoice_workflow(): void
    {
        Http::fake([
            'https://api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_a1b2c3d4e5',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_a1b2c3d4e5',
                'payment_intent' => 'pi_test_99887766',
            ], 200),
        ]);

        $seller = $this->createApprovedBusiness('GreenMetal Sdn Bhd', '202301009999');
        $buyer = $this->createApprovedBusiness('Klang Packaging Bhd', '202401008888');
        $item = $this->createReusableItem($seller, ['price' => 50.00, 'quantity' => 20]);

        // 1. Buyer places order (even if browser tries sending tampered total_price=1.00, server computes 5 * 50.00 = 250.00)
        $orderResp = $this->actingAs($buyer)->post(route('business.marketplace.exchange', $item), [
            'quantity' => 5,
            'total_price' => 1.00,
            'proceed_to_checkout' => 1,
            'notes' => 'Pickup next Monday',
        ]);

        $exchange = MaterialExchange::where('item_id', $item->id)->firstOrFail();
        $orderResp->assertRedirect(route('business.orders.checkout', $exchange));
        $this->assertEquals(250.00, (float) $exchange->total_price);
        $this->assertEquals(50.00, (float) $exchange->unit_price);
        $this->assertSame('pending_payment', $exchange->payment_status);

        // 2. Buyer views Order Review page
        $this->actingAs($buyer)
            ->get(route('business.orders.checkout', $exchange))
            ->assertOk()
            ->assertSee('RM 250.00')
            ->assertSee('GreenMetal Sdn Bhd')
            ->assertSee('Pay Now');

        // 3. Buyer clicks Pay Now -> creates Stripe Checkout Session with MYR 25000 sen
        $checkoutResp = $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $exchange));

        $checkoutResp->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_a1b2c3d4e5');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
                && $request['line_items[0][price_data][currency]'] === 'myr'
                && $request['line_items[0][price_data][unit_amount]'] === '25000'
                && ! empty($request->header('Idempotency-Key'));
        });

        $payment = Payment::where('material_exchange_id', $exchange->id)->firstOrFail();
        $this->assertSame('cs_test_a1b2c3d4e5', $payment->stripe_checkout_session_id);
        $this->assertSame(25000, $payment->amount_sen);
        $this->assertSame('myr', $payment->currency);
        $this->assertSame('pending', $payment->status);

        // 4. Success redirect ALONE must NOT mark the order as paid!
        $successResp = $this->actingAs($buyer)
            ->get(route('business.payments.stripe.success', ['session_id' => 'cs_test_a1b2c3d4e5']));
        $successResp->assertRedirect(route('business.invoices.index', ['tab' => 'orders']));

        $exchange->refresh();
        $this->assertSame('pending_payment', $exchange->payment_status);
        $this->assertDatabaseCount('invoice', 0);

        // 5. Invalid webhook signature is strictly rejected (400)
        $webhookPayload = json_encode([
            'id' => 'evt_test_paid_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_a1b2c3d4e5',
                    'payment_status' => 'paid',
                    'payment_intent' => 'pi_test_99887766',
                    'currency' => 'myr',
                    'amount_total' => 25000,
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

        $exchange->refresh();
        $this->assertSame('pending_payment', $exchange->payment_status);

        // 6. Valid signed Stripe webhook confirms payment, deducts stock ONCE, and generates invoice
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
        $this->assertSame('pi_test_99887766', $payment->stripe_payment_intent_id);
        $this->assertEquals(15.00, (float) $item->quantity);

        $invoice = Invoice::where('material_exchange_id', $exchange->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^ECO-INV-\d{4}-00001$/', $invoice->invoice_number);
        $this->assertEquals(250.00, (float) $invoice->total_amount);
        $this->assertEquals(0.00, (float) $invoice->tax_amount);
        $this->assertNull($invoice->tax_label);

        // 7. Duplicate webhook delivery is idempotent (no duplicate invoice, no second stock deduction)
        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $validSig, 'CONTENT_TYPE' => 'application/json'],
            $webhookPayload
        )->assertOk()->assertJsonPath('status', 'duplicate_event_ignored');

        // Even a second event ID for the same paid order must not deduct stock or duplicate invoice
        $secondEventPayload = str_replace('evt_test_paid_001', 'evt_test_paid_002', $webhookPayload);
        $secondSig = StripePaymentService::buildTestSignatureHeader($secondEventPayload, $this->webhookSecret);
        $this->call(
            'POST',
            route('webhooks.stripe'),
            [],
            [],
            [],
            ['HTTP_STRIPE_SIGNATURE' => $secondSig, 'CONTENT_TYPE' => 'application/json'],
            $secondEventPayload
        )->assertOk()->assertJsonPath('status', 'already_paid');

        $item->refresh();
        $this->assertEquals(15.00, (float) $item->quantity);
        $this->assertDatabaseCount('invoice', 1);

        // 8. Duplicate payment attempt is blocked once paid
        $dupCheckout = $this->actingAs($buyer)->post(route('business.payments.stripe.checkout', $exchange));
        $dupCheckout->assertRedirect(route('business.invoices.show', $invoice));
        Http::assertSentCount(1); // Stripe API was NOT called a second time

        // 9. Invoice view & PDF download work for both buyer and seller
        $this->actingAs($buyer)
            ->get(route('business.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('RM 250.00')
            ->assertSee('GreenMetal Sdn Bhd')
            ->assertSee('Klang Packaging Bhd')
            ->assertSee('pi_test_99887766');

        $pdfResponse = $this->actingAs($buyer)->get(route('business.invoices.pdf', $invoice));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('Content-Type', 'application/pdf');
        $pdfBody = $pdfResponse->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdfBody);
        $this->assertStringContainsString('%%EOF', $pdfBody);
        $this->assertStringContainsString($invoice->invoice_number, $pdfBody);

        // Seller can also view and download PDF
        $this->actingAs($seller)->get(route('business.invoices.show', $invoice))->assertOk();
        $this->actingAs($seller)->get(route('business.invoices.pdf', $invoice))->assertOk();

        // 10. Business dashboard invoice hub tabs render cleanly
        $this->actingAs($buyer)->get(route('business.invoices.index', ['tab' => 'orders']))->assertOk()->assertSee($exchange->order_reference);
        $this->actingAs($buyer)->get(route('business.invoices.index', ['tab' => 'payments']))->assertOk()->assertSee('pi_test_99887766');
        $this->actingAs($buyer)->get(route('business.invoices.index', ['tab' => 'invoices']))->assertOk()->assertSee($invoice->invoice_number);
    }

    public function test_failed_payment_and_cancelled_checkout_do_not_mark_paid_or_create_invoice(): void
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
        $cancelResp = $this->actingAs($buyer)->get(route('business.payments.stripe.cancel', $exchange));
        $cancelResp->assertRedirect(route('business.orders.checkout', $exchange));

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
        $this->assertSame('Your card has insufficient funds.', $payment->failure_reason);
        $this->assertEquals(10.00, (float) $item->quantity); // Stock untouched
        $this->assertDatabaseCount('invoice', 0);
    }

    public function test_unrelated_business_cannot_access_other_business_invoice_or_checkout(): void
    {
        $seller = $this->createApprovedBusiness('Alpha Supply Sdn Bhd');
        $buyer = $this->createApprovedBusiness('Beta Builders Sdn Bhd');
        $intruder = $this->createApprovedBusiness('Intruder Corp Sdn Bhd');
        $item = $this->createReusableItem($seller, ['price' => 100.00]);

        $exchange = MaterialExchange::create([
            'order_reference' => 'ECO-ORD-2026-SEC01',
            'invoice_number' => 'ECO-INV-2026-00001',
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

        $invoice = Invoice::create([
            'invoice_number' => 'ECO-INV-2026-00001',
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
            'issued_at' => now(),
            'paid_at' => now(),
        ]);

        // Intruder blocked from invoice HTML, PDF download, and checkout
        $this->actingAs($intruder)->get(route('business.invoices.show', $invoice))->assertForbidden();
        $this->actingAs($intruder)->get(route('business.invoices.pdf', $invoice))->assertForbidden();
        $this->actingAs($intruder)->get(route('business.orders.checkout', $exchange))->assertForbidden();
        $this->actingAs($intruder)->post(route('business.payments.stripe.checkout', $exchange))->assertForbidden();

        // Seller cannot initiate buyer checkout either
        $this->actingAs($seller)->post(route('business.payments.stripe.checkout', $exchange))->assertForbidden();
    }

    public function test_payment_restrictions_for_free_borrow_and_non_reusable_items(): void
    {
        Http::fake();

        $seller = $this->createApprovedBusiness('Restriction Seller Bhd');
        $buyer = $this->createApprovedBusiness('Restriction Buyer Bhd');

        // 1. Free item does NOT trigger Stripe payment
        $freeItem = $this->createReusableItem($seller, [
            'title' => 'Free Cardboard Sheets',
            'name' => 'Free Cardboard Sheets',
            'listing_type' => 'free',
            'is_free' => true,
            'price' => 0.00,
            'quantity' => 20,
        ]);

        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $freeItem), [
                'quantity' => 5,
                'proceed_to_checkout' => 1,
            ])
            ->assertRedirect(route('business.transactions', ['tab' => 'purchases']));

        $freeOrder = MaterialExchange::where('item_id', $freeItem->id)->firstOrFail();
        $this->assertEquals(0.00, (float) $freeOrder->total_price);
        $this->assertSame('paid', $freeOrder->payment_status);

        // Attempting Stripe checkout on free order makes zero Stripe HTTP calls
        $this->actingAs($buyer)->post(route('business.payments.stripe.checkout', $freeOrder));
        Http::assertNothingSent();

        // 2. Borrow/Lend item does NOT trigger Stripe payment
        $borrowItem = $this->createReusableItem($seller, [
            'title' => 'Borrowable Steel Crates',
            'name' => 'Borrowable Steel Crates',
            'listing_type' => 'borrow',
            'price' => 25.00,
            'quantity' => 10,
        ]);

        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $borrowItem), [
                'quantity' => 2,
                'proceed_to_checkout' => 1,
            ])
            ->assertRedirect(route('business.transactions', ['tab' => 'purchases']));

        $borrowOrder = MaterialExchange::where('item_id', $borrowItem->id)->firstOrFail();
        $this->assertSame('borrow_pending', $borrowOrder->payment_status);

        $this->actingAs($buyer)
            ->post(route('business.payments.stripe.checkout', $borrowOrder))
            ->assertSessionHasErrors('payment');
        Http::assertNothingSent();

        // 3. Non-Reusable item cannot be purchased at all
        $nonReusableItem = $this->createReusableItem($seller, [
            'title' => 'Contaminated Oil Sludge',
            'name' => 'Contaminated Oil Sludge',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 40.00,
            'quantity' => 10,
        ]);

        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $nonReusableItem), [
                'quantity' => 2,
            ])
            ->assertStatus(400);

        $this->assertDatabaseMissing('material_exchange', ['item_id' => $nonReusableItem->id]);
        Http::assertNothingSent();
    }
}
