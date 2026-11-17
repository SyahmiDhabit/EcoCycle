<?php

namespace App\Http\Controllers;

use App\Models\MaterialExchange;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\StripePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class StripePaymentController extends Controller
{
    public function __construct(
        private readonly StripePaymentService $stripeService,
        private readonly InvoiceService $invoiceService,
    ) {}

    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    /**
     * Order Review & Pay Now confirmation screen for the buyer.
     */
    public function review(MaterialExchange $exchange): View|RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        abort_unless($exchange->requester_business_id === $business->id, 403, 'Only the buyer can access checkout for this order.');

        $exchange->load(['item', 'provider', 'requester', 'invoice', 'payments']);

        if ($exchange->payment_status === 'paid' && $exchange->invoice) {
            return redirect()->route('business.invoices.show', $exchange->invoice)
                ->with('status', "Order {$exchange->resolvedOrderReference()} is already paid.");
        }

        $unitPrice = (float) $exchange->unit_price > 0
            ? (float) $exchange->unit_price
            : (float) ($exchange->item?->price ?? 0);

        $totals = $this->invoiceService->calculateTotals((float) $exchange->quantity, $unitPrice);

        return view('business.invoices.checkout', [
            'business' => $business,
            'exchange' => $exchange,
            'totals' => $totals,
            'unitPrice' => $unitPrice,
            'stripePublishableKey' => (string) config('services.stripe.key', ''),
            'stripeCurrency' => strtoupper((string) config('services.stripe.currency', 'myr')),
        ]);
    }

    /**
     * Initiate a server-side Stripe Checkout Session and redirect buyer to Stripe.
     */
    public function checkout(Request $request, MaterialExchange $exchange): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        abort_unless($exchange->requester_business_id === $business->id, 403, 'Only the buyer can pay for this order.');

        if ($exchange->payment_status === 'paid') {
            if ($exchange->invoice) {
                return redirect()->route('business.invoices.show', $exchange->invoice)
                    ->with('status', 'This order has already been paid.');
            }

            return redirect()->route('business.invoices.index', ['tab' => 'orders'])
                ->with('status', 'This order has already been paid.');
        }

        $successUrl = route('business.payments.stripe.success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('business.payments.stripe.cancel', $exchange);

        try {
            $result = $this->stripeService->createCheckoutSession($exchange, $business, $successUrl, $cancelUrl);
        } catch (RuntimeException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->away($result['checkout_url']);
    }

    /**
     * Return URL after Stripe Checkout completion.
     * IMPORTANT: Never marks an order as paid based on redirect alone — relies strictly on verified Stripe webhooks.
     */
    public function success(Request $request): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $sessionId = trim((string) $request->query('session_id', ''));
        if ($sessionId === '') {
            return redirect()->route('business.invoices.index', ['tab' => 'orders'])
                ->with('status', 'Checkout session returned. Order payment status is verified automatically via Stripe webhook.');
        }

        $payment = Payment::query()
            ->with(['exchange.invoice'])
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if (! $payment || $payment->buyer_business_id !== $business->id) {
            abort(403, 'Unauthorized payment session access.');
        }

        $exchange = $payment->exchange;

        if ($exchange && $exchange->payment_status === 'paid' && $exchange->invoice) {
            return redirect()->route('business.invoices.show', $exchange->invoice)
                ->with('status', "Payment verified via Stripe. Invoice {$exchange->invoice->invoice_number} is ready.");
        }

        return redirect()->route('business.invoices.index', ['tab' => 'orders'])
            ->with('status', 'Payment submitted to Stripe. Your order will automatically update to Paid and generate your PDF invoice as soon as webhook verification completes.');
    }

    /**
     * Return URL when the buyer cancels Stripe Checkout.
     */
    public function cancel(Request $request, MaterialExchange $exchange): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        abort_unless($exchange->requester_business_id === $business->id, 403, 'Only the buyer can cancel checkout for this order.');

        if ($exchange->payment_status !== 'paid') {
            Payment::query()
                ->where('material_exchange_id', $exchange->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'cancelled',
                    'failure_reason' => 'Buyer cancelled Stripe Checkout.',
                ]);

            $exchange->update([
                'payment_status' => 'cancelled',
            ]);
        }

        return redirect()->route('business.orders.checkout', $exchange)
            ->withErrors(['payment' => 'Payment was cancelled. You may review the order details and click Pay Now whenever you are ready.']);
    }
}
