<?php

namespace App\Services;

use App\Models\BusinessNotification;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\MaterialExchange;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class StripePaymentService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    /**
     * Create a Stripe Checkout Session on the server using verified order prices in MYR.
     *
     * Note on Marketplace Seller Settlement:
     * In this initial test implementation, payments are collected by the EcoCycle platform's
     * configured Stripe test account (STRIPE_SECRET). Multi-seller automated transfers require
     * Stripe Connect Express/Custom onboarding for each Malaysian SME seller and are not enabled here.
     *
     * @return array{payment: Payment, session_id: string, checkout_url: string}
     */
    public function createCheckoutSession(
        MaterialExchange $exchange,
        User $buyer,
        string $successUrl,
        string $cancelUrl
    ): array {
        $exchange->loadMissing(['item', 'provider', 'requester']);

        if ($exchange->requester_business_id !== $buyer->id) {
            throw new RuntimeException('You are not authorized to pay for this order.');
        }

        $item = $exchange->item;
        if (! $item) {
            throw new RuntimeException('The associated marketplace item could not be found.');
        }

        if (($item->reusability ?? 'reusable') !== 'reusable') {
            throw new RuntimeException('Non-Reusable items cannot be purchased on the Marketplace.');
        }

        $listingType = $exchange->resolvedListingType();
        if ($listingType === 'free' || (bool) $item->is_free) {
            throw new RuntimeException('Free items do not require Stripe payment.');
        }

        if ($listingType !== 'buy') {
            throw new RuntimeException('Only Sell listings are eligible for Stripe payment. Exchange and Borrow/Lend are not supported.');
        }

        if ($exchange->payment_status === 'paid') {
            throw new RuntimeException('This order has already been paid.');
        }

        // Recalculate price strictly on the backend (never trust client-submitted totals)
        $unitPrice = (float) $exchange->unit_price > 0
            ? (float) $exchange->unit_price
            : (float) $item->price;

        $totals = $this->invoiceService->calculateTotals((float) $exchange->quantity, $unitPrice);

        if ($totals['amount_sen'] < 200) {
            throw new RuntimeException('Stripe MYR payments require a minimum total amount of RM 2.00.');
        }

        $secret = trim((string) config('services.stripe.secret'));
        if ($secret === '') {
            throw new RuntimeException('Stripe API secret key is not configured.');
        }

        $currency = strtolower((string) config('services.stripe.currency', 'myr'));
        if ($currency !== 'myr') {
            $currency = 'myr';
        }

        // Ensure order has server-calculated totals and order reference
        if (empty($exchange->order_reference)) {
            $exchange->order_reference = $exchange->resolvedOrderReference();
        }
        $exchange->unit_price = $unitPrice;
        $exchange->total_price = $totals['total'];
        $exchange->save();

        // Mark any previous uncompleted pending session for this order as expired/superseded before creating a new attempt
        Payment::query()
            ->where('material_exchange_id', $exchange->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $idempotencyKey = 'ecocycle_order_'.$exchange->id.'_'.Str::uuid()->toString();

        $payment = Payment::create([
            'material_exchange_id' => $exchange->id,
            'buyer_business_id' => $buyer->id,
            'seller_business_id' => $exchange->provider_business_id,
            'provider' => 'stripe',
            'settlement_account_type' => 'platform_test_account',
            'idempotency_key' => $idempotencyKey,
            'currency' => $currency,
            'amount' => $totals['total'],
            'amount_sen' => $totals['amount_sen'],
            'status' => 'pending',
        ]);

        $qtyLabel = rtrim(rtrim(number_format((float) $exchange->quantity, 2, '.', ''), '0'), '.');
        $productName = ($item->name ?: $item->title)." ({$qtyLabel} {$exchange->unit})";
        $sellerName = $exchange->provider?->business_name ?: ($exchange->provider?->name ?? 'EcoCycle Seller');

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $exchange->id,
            'customer_email' => $buyer->email,
            'metadata[exchange_id]' => (string) $exchange->id,
            'metadata[payment_id]' => (string) $payment->id,
            'metadata[order_reference]' => $exchange->resolvedOrderReference(),
            'metadata[buyer_business_id]' => (string) $buyer->id,
            'metadata[seller_business_id]' => (string) $exchange->provider_business_id,
            'metadata[settlement_account_type]' => 'platform_test_account',
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => (string) $totals['amount_sen'],
            'line_items[0][price_data][product_data][name]' => Str::limit($productName, 120, ''),
            'line_items[0][price_data][product_data][description]' => Str::limit("Order {$exchange->resolvedOrderReference()} from {$sellerName}", 250, ''),
            'line_items[0][quantity]' => '1',
            'payment_intent_data[metadata][exchange_id]' => (string) $exchange->id,
            'payment_intent_data[metadata][payment_id]' => (string) $payment->id,
            'payment_intent_data[metadata][order_reference]' => $exchange->resolvedOrderReference(),
        ];

        $apiBase = rtrim((string) config('services.stripe.api_base', 'https://api.stripe.com/v1'), '/');

        $response = Http::withBasicAuth($secret, '')
            ->withHeaders([
                'Idempotency-Key' => $idempotencyKey,
            ])
            ->asForm()
            ->timeout(20)
            ->post("{$apiBase}/checkout/sessions", $payload);

        if (! $response->successful()) {
            $errMsg = (string) ($response->json('error.message') ?? 'Unable to initiate Stripe Checkout Session.');
            $payment->update([
                'status' => 'failed',
                'failure_reason' => Str::limit($errMsg, 250, ''),
            ]);
            Log::error('Stripe checkout session creation failed', [
                'exchange_id' => $exchange->id,
                'status' => $response->status(),
                'error' => $errMsg,
            ]);
            throw new RuntimeException($errMsg);
        }

        $data = $response->json();
        $sessionId = (string) ($data['id'] ?? '');
        $checkoutUrl = (string) ($data['url'] ?? '');
        $paymentIntentId = ! empty($data['payment_intent']) && is_string($data['payment_intent'])
            ? $data['payment_intent']
            : null;

        if ($sessionId === '' || $checkoutUrl === '') {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => 'Stripe did not return a valid Checkout Session URL.',
            ]);
            throw new RuntimeException('Stripe did not return a valid Checkout Session URL.');
        }

        $payment->update([
            'stripe_checkout_session_id' => $sessionId,
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        return [
            'payment' => $payment,
            'session_id' => $sessionId,
            'checkout_url' => $checkoutUrl,
        ];
    }

    /**
     * Verify Stripe webhook signature header (t=...,v1=...) using constant-time HMAC-SHA256.
     */
    public function verifyWebhookSignature(
        string $payload,
        ?string $signatureHeader,
        ?string $webhookSecret = null,
        int $toleranceSeconds = 300
    ): bool {
        $secret = trim((string) ($webhookSecret ?? config('services.stripe.webhook_secret')));
        if ($secret === '' || $secret === 'your_webhook_signing_secret' || empty($signatureHeader)) {
            return false;
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) !== 2) {
                continue;
            }
            if ($kv[0] === 't') {
                $timestamp = (int) $kv[1];
            } elseif ($kv[0] === 'v1') {
                $signatures[] = $kv[1];
            }
        }

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        if (abs(time() - $timestamp) > $toleranceSeconds) {
            return false;
        }

        $signedPayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a valid Stripe-Signature header for testing webhook verification.
     */
    public static function buildTestSignatureHeader(string $payload, string $secret, ?int $timestamp = null): string
    {
        $ts = $timestamp ?? time();
        $sig = hash_hmac('sha256', "{$ts}.{$payload}", $secret);

        return "t={$ts},v1={$sig}";
    }

    /**
     * Process a verified Stripe webhook event idempotently.
     *
     * @param  array<string, mixed>  $event
     * @return array{handled: bool, status: string, invoice_id?: int|null}
     */
    public function processWebhookEvent(array $event): array
    {
        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['type'] ?? '');
        $object = (array) ($event['data']['object'] ?? []);

        if ($eventId !== '' && Payment::query()->where('stripe_event_id', $eventId)->exists()) {
            return ['handled' => true, 'status' => 'duplicate_event_ignored'];
        }

        return match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($eventId, $object),
            'checkout.session.async_payment_succeeded' => $this->handleCheckoutSessionPaid($eventId, $object),
            'checkout.session.async_payment_failed' => $this->handlePaymentFailure($eventId, $object, 'Async payment failed.'),
            'checkout.session.expired' => $this->handleCheckoutSessionExpired($eventId, $object),
            'payment_intent.payment_failed' => $this->handlePaymentFailure(
                $eventId,
                $object,
                (string) ($object['last_payment_error']['message'] ?? 'Payment failed.')
            ),
            default => ['handled' => false, 'status' => 'ignored_event_type'],
        };
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array{handled: bool, status: string, invoice_id?: int|null}
     */
    private function handleCheckoutSessionCompleted(string $eventId, array $session): array
    {
        $stripePaymentStatus = strtolower((string) ($session['payment_status'] ?? ''));

        // Only mark paid when Stripe explicitly reports payment_status === 'paid'
        if ($stripePaymentStatus !== 'paid') {
            return ['handled' => true, 'status' => 'awaiting_async_payment'];
        }

        return $this->handleCheckoutSessionPaid($eventId, $session);
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array{handled: bool, status: string, invoice_id?: int|null}
     */
    private function handleCheckoutSessionPaid(string $eventId, array $session): array
    {
        $sessionId = (string) ($session['id'] ?? '');
        $paymentIntentId = is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : null;
        $metadata = (array) ($session['metadata'] ?? []);
        $exchangeId = (int) ($metadata['exchange_id'] ?? ($session['client_reference_id'] ?? 0));
        $paymentId = (int) ($metadata['payment_id'] ?? 0);

        $currency = strtolower((string) ($session['currency'] ?? 'myr'));
        $amountTotalSen = isset($session['amount_total']) ? (int) $session['amount_total'] : null;

        $paymentMethodTypes = (array) ($session['payment_method_types'] ?? []);
        $paymentMethod = ! empty($paymentMethodTypes) ? (string) $paymentMethodTypes[0] : 'card';

        return DB::transaction(function () use (
            $eventId,
            $sessionId,
            $paymentIntentId,
            $exchangeId,
            $paymentId,
            $currency,
            $amountTotalSen,
            $paymentMethod
        ): array {
            $payment = $this->resolvePaymentForUpdate($paymentId, $sessionId, $paymentIntentId);
            $exchange = $this->resolveExchangeForUpdate($exchangeId, $payment?->material_exchange_id);

            if (! $exchange) {
                Log::warning('Stripe webhook received for unknown order', [
                    'event_id' => $eventId,
                    'session_id' => $sessionId,
                    'exchange_id' => $exchangeId,
                ]);

                return ['handled' => false, 'status' => 'order_not_found'];
            }

            // Idempotency: if order is already paid, ensure invoice exists and skip duplicate mutations
            if ($exchange->payment_status === 'paid') {
                $existingInvoice = $exchange->invoice ?: $this->invoiceService->generateForOrder($exchange, $payment);

                return [
                    'handled' => true,
                    'status' => 'already_paid',
                    'invoice_id' => $existingInvoice->id,
                ];
            }

            // Server-side verification of currency & amount
            $expectedSen = $payment ? $payment->amount_sen : (int) round((float) $exchange->total_price * 100);
            if ($currency !== 'myr') {
                Log::error('Stripe webhook currency mismatch', ['expected' => 'myr', 'received' => $currency, 'exchange_id' => $exchange->id]);

                return ['handled' => false, 'status' => 'currency_mismatch'];
            }
            if ($amountTotalSen !== null && $expectedSen > 0 && $amountTotalSen !== $expectedSen) {
                Log::error('Stripe webhook amount mismatch', ['expected_sen' => $expectedSen, 'received_sen' => $amountTotalSen, 'exchange_id' => $exchange->id]);

                return ['handled' => false, 'status' => 'amount_mismatch'];
            }

            if (! $payment) {
                $payment = Payment::create([
                    'material_exchange_id' => $exchange->id,
                    'buyer_business_id' => $exchange->requester_business_id,
                    'seller_business_id' => $exchange->provider_business_id,
                    'provider' => 'stripe',
                    'settlement_account_type' => 'platform_test_account',
                    'stripe_checkout_session_id' => $sessionId !== '' ? $sessionId : null,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'stripe_event_id' => $eventId !== '' ? $eventId : null,
                    'currency' => 'myr',
                    'amount' => $exchange->total_price,
                    'amount_sen' => $expectedSen,
                    'payment_method' => $paymentMethod,
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            } else {
                $payment->update([
                    'stripe_checkout_session_id' => $payment->stripe_checkout_session_id ?: ($sessionId !== '' ? $sessionId : null),
                    'stripe_payment_intent_id' => $paymentIntentId ?: $payment->stripe_payment_intent_id,
                    'stripe_event_id' => $eventId !== '' ? $eventId : $payment->stripe_event_id,
                    'payment_method' => $paymentMethod,
                    'status' => 'paid',
                    'failure_reason' => null,
                    'paid_at' => now(),
                ]);
            }

            $reference = $payment->referenceDisplay();

            $exchange->update([
                'payment_status' => 'paid',
                'payment_reference' => $reference,
                'paid_at' => $payment->paid_at ?: now(),
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Deduct purchased stock once on payment confirmation
            if ($listing = $exchange->listing) {
                $newQty = max(0, (float) $listing->quantity - (float) $exchange->quantity);
                $listing->update([
                    'quantity' => $newQty,
                    'status' => $newQty <= 0 ? 'sold' : 'active',
                ]);

                InventoryTransaction::create([
                    'business_id' => $listing->business_id,
                    'inventory_item_id' => $listing->id,
                    'transaction_type' => 'stock_out',
                    'quantity_change' => -(float) $exchange->quantity,
                    'quantity_after' => $newQty,
                    'notes' => "Sold via Order {$exchange->resolvedOrderReference()} (Stripe Ref: {$reference})",
                ]);
            }

            // Generate official Malaysian PDF-ready Invoice idempotently
            $invoice = $this->invoiceService->generateForOrder($exchange, $payment);

            // Notify Buyer & Seller
            BusinessNotification::send(
                $exchange->requester_business_id,
                'Payment Confirmed & Receipt Ready',
                "Your payment of RM ".number_format((float) $invoice->total_amount, 2)." for Order {$exchange->resolvedOrderReference()} is confirmed. Receipt {$invoice->invoice_number} is ready to download.",
                'payment',
                "/business/invoices/{$invoice->id}",
                'green'
            );

            BusinessNotification::send(
                $exchange->provider_business_id,
                'Order Payment Received',
                "Payment of RM ".number_format((float) $invoice->total_amount, 2)." for Order {$exchange->resolvedOrderReference()} has been verified via Stripe. Receipt {$invoice->invoice_number} generated.",
                'payment',
                "/business/invoices/{$invoice->id}",
                'green'
            );

            return [
                'handled' => true,
                'status' => 'paid',
                'invoice_id' => $invoice->id,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $object
     * @return array{handled: bool, status: string}
     */
    private function handlePaymentFailure(string $eventId, array $object, string $reason): array
    {
        $objectId = (string) ($object['id'] ?? '');
        $metadata = (array) ($object['metadata'] ?? []);
        $exchangeId = (int) ($metadata['exchange_id'] ?? ($object['client_reference_id'] ?? 0));
        $paymentId = (int) ($metadata['payment_id'] ?? 0);
        $paymentIntentId = str_starts_with($objectId, 'pi_') ? $objectId : (is_string($object['payment_intent'] ?? null) ? $object['payment_intent'] : null);
        $sessionId = str_starts_with($objectId, 'cs_') ? $objectId : '';

        return DB::transaction(function () use ($eventId, $paymentId, $sessionId, $paymentIntentId, $exchangeId, $reason): array {
            $payment = $this->resolvePaymentForUpdate($paymentId, $sessionId, $paymentIntentId);
            $exchange = $this->resolveExchangeForUpdate($exchangeId, $payment?->material_exchange_id);

            if ($exchange && $exchange->payment_status === 'paid') {
                return ['handled' => true, 'status' => 'already_paid'];
            }

            if ($payment && $payment->status !== 'paid') {
                $payment->update([
                    'status' => 'failed',
                    'stripe_event_id' => $eventId !== '' ? $eventId : $payment->stripe_event_id,
                    'stripe_payment_intent_id' => $paymentIntentId ?: $payment->stripe_payment_intent_id,
                    'failure_reason' => Str::limit($reason, 250, ''),
                ]);
            }

            if ($exchange && $exchange->payment_status !== 'paid') {
                $exchange->update([
                    'payment_status' => 'failed',
                ]);
            }

            return ['handled' => true, 'status' => 'failed'];
        });
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array{handled: bool, status: string}
     */
    private function handleCheckoutSessionExpired(string $eventId, array $session): array
    {
        $sessionId = (string) ($session['id'] ?? '');
        $metadata = (array) ($session['metadata'] ?? []);
        $exchangeId = (int) ($metadata['exchange_id'] ?? ($session['client_reference_id'] ?? 0));
        $paymentId = (int) ($metadata['payment_id'] ?? 0);

        return DB::transaction(function () use ($eventId, $paymentId, $sessionId, $exchangeId): array {
            $payment = $this->resolvePaymentForUpdate($paymentId, $sessionId, null);
            $exchange = $this->resolveExchangeForUpdate($exchangeId, $payment?->material_exchange_id);

            if ($exchange && $exchange->payment_status === 'paid') {
                return ['handled' => true, 'status' => 'already_paid'];
            }

            if ($payment && $payment->status === 'pending') {
                $payment->update([
                    'status' => 'cancelled',
                    'stripe_event_id' => $eventId !== '' ? $eventId : $payment->stripe_event_id,
                    'failure_reason' => 'Checkout session expired before payment was completed.',
                ]);
            }

            if ($exchange && $exchange->payment_status !== 'paid') {
                $exchange->update([
                    'payment_status' => 'cancelled',
                ]);
            }

            return ['handled' => true, 'status' => 'cancelled'];
        });
    }

    private function resolvePaymentForUpdate(int $paymentId, string $sessionId, ?string $paymentIntentId): ?Payment
    {
        if ($paymentId > 0) {
            $found = Payment::query()->where('id', $paymentId)->lockForUpdate()->first();
            if ($found) {
                return $found;
            }
        }

        if ($sessionId !== '') {
            $found = Payment::query()->where('stripe_checkout_session_id', $sessionId)->lockForUpdate()->first();
            if ($found) {
                return $found;
            }
        }

        if ($paymentIntentId) {
            $found = Payment::query()->where('stripe_payment_intent_id', $paymentIntentId)->lockForUpdate()->first();
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    private function resolveExchangeForUpdate(int $exchangeId, ?int $fallbackExchangeId): ?MaterialExchange
    {
        $targetId = $exchangeId > 0 ? $exchangeId : (int) ($fallbackExchangeId ?? 0);
        if ($targetId <= 0) {
            return null;
        }

        return MaterialExchange::query()->where('id', $targetId)->lockForUpdate()->first();
    }
}
