<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\MaterialExchange;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}
    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $business */
        $business = Auth::user();
        if ($business->account_status !== 'approved') {
            return redirect()->route('business.dashboard');
        }

        $viewType = $request->input('tab', 'all'); // all, sales, purchases

        $query = MaterialExchange::with(['listing', 'provider', 'requester', 'invoice', 'latestPayment'])->latest();

        if ($viewType === 'sales') {
            $query->where('provider_business_id', $business->id);
        } elseif ($viewType === 'purchases') {
            $query->where('requester_business_id', $business->id);
        } else {
            $query->where(function ($q) use ($business) {
                $q->where('provider_business_id', $business->id)
                    ->orWhere('requester_business_id', $business->id);
            });
        }

        if ($status = $request->input('payment_status')) {
            $query->where('payment_status', $status);
        }

        $exchanges = $query->paginate(15)->withQueryString();

        // Calculate KPI Metrics
        $allBusinessExchanges = MaterialExchange::where(function ($q) use ($business) {
            $q->where('provider_business_id', $business->id)
                ->orWhere('requester_business_id', $business->id);
        })->get();

        $totalRevenueRm = $allBusinessExchanges->where('provider_business_id', $business->id)
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $totalPurchasesRm = $allBusinessExchanges->where('requester_business_id', $business->id)
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $pendingPaymentsCount = $allBusinessExchanges->where('payment_status', '!=', 'paid')
            ->where('total_price', '>', 0)
            ->count();

        $completedExchangesCount = $allBusinessExchanges->where('status', 'completed')->count();

        return view('business.transactions.index', [
            'business' => $business,
            'exchanges' => $exchanges,
            'currentTab' => $viewType,
            'selectedStatus' => $status,
            'totalRevenueRm' => $totalRevenueRm,
            'totalPurchasesRm' => $totalPurchasesRm,
            'pendingPaymentsCount' => $pendingPaymentsCount,
            'completedExchangesCount' => $completedExchangesCount,
        ]);
    }

    public function store(Request $request, Item $listing): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        abort_if($listing->business_id === $business->id, 400, 'You cannot order materials from your own business.');
        abort_if(($listing->reusability ?? 'reusable') !== 'reusable', 400, 'Non-Reusable items cannot be purchased on the Marketplace.');

        $listingType = strtolower(trim((string) ($listing->listing_type ?: 'buy')));
        abort_unless(
            in_array($listingType, ['buy', 'free'], true),
            422,
            'Only Sell and Free listing types are supported.'
        );

        $availableQty = (float) $listing->quantity;
        abort_if($availableQty <= 0, 400, 'This item is currently out of stock.');

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01', "max:{$availableQty}"],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.required' => 'Please enter the quantity you wish to order or claim.',
            'quantity.min' => 'Quantity must be at least 0.01.',
            'quantity.max' => "Requested quantity exceeds available stock of {$availableQty} {$listing->unit}.",
        ]);

        $orderQty = (float) $validated['quantity'];
        $isFree = (bool) $listing->is_free || $listingType === 'free';
        $normalizedType = $isFree ? 'free' : 'buy';

        // Calculate price strictly on the backend (unit price * quantity)
        $unitPrice = $isFree ? 0.00 : round((float) ($listing->price ?? 0), 2);
        $totals = $this->invoiceService->calculateTotals($orderQty, $unitPrice);
        $totalPrice = $isFree ? 0.00 : $totals['total'];

        $orderRef = 'ECO-ORD-'.date('Y').'-'.strtoupper(Str::random(6));
        $initialPaymentStatus = $isFree ? 'free' : 'pending';

        $exchange = DB::transaction(function () use ($listing, $business, $normalizedType, $orderQty, $unitPrice, $totalPrice, $orderRef, $isFree, $initialPaymentStatus, $validated): MaterialExchange {
            // Lock item row inside transaction to strictly prevent overselling
            $lockedListing = Item::query()
                ->where('id', $listing->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $lockedListing->quantity < $orderQty) {
                throw ValidationException::withMessages([
                    'quantity' => "Requested quantity exceeds available stock of {$lockedListing->quantity} {$lockedListing->unit}.",
                ]);
            }

            $now = now();

            $exchange = MaterialExchange::create([
                'invoice_number' => $orderRef,
                'order_reference' => $orderRef,
                'item_id' => $lockedListing->id,
                'provider_business_id' => $lockedListing->business_id,
                'requester_business_id' => $business->id,
                'listing_type' => $normalizedType,
                'quantity' => $orderQty,
                'unit' => $lockedListing->unit,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'status' => $isFree ? 'completed' : 'pending',
                'payment_status' => $initialPaymentStatus,
                'payment_reference' => $isFree ? 'FREE' : null,
                'paid_at' => $isFree ? $now : null,
                'completed_at' => $isFree ? $now : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create the single Payment record for this order (RM 0.00 Free or Pending MYR)
            $payment = Payment::create([
                'material_exchange_id' => $exchange->id,
                'buyer_business_id' => $business->id,
                'seller_business_id' => $lockedListing->business_id,
                'currency' => 'myr',
                'amount' => $totalPrice,
                'status' => $initialPaymentStatus,
                'paid_at' => $isFree ? $now : null,
            ]);

            // For Free items: deduct stock atomically once and generate one Free receipt (no Stripe)
            if ($isFree) {
                $newQty = max(0, (float) $lockedListing->quantity - $orderQty);
                $newStatus = $newQty <= 0 ? 'sold' : 'active';
                $lockedListing->update([
                    'quantity' => $newQty,
                    'status' => $newStatus,
                ]);

                InventoryTransaction::create([
                    'business_id' => $lockedListing->business_id,
                    'inventory_item_id' => $lockedListing->id,
                    'transaction_type' => 'stock_out',
                    'quantity_change' => -$orderQty,
                    'quantity_after' => $newQty,
                    'notes' => "Free material claimed via Order #{$orderRef}",
                ]);

                $this->invoiceService->generateForOrder($exchange, $payment);
            }

            return $exchange;
        });

        // Notifications
        BusinessNotification::send(
            $listing->business_id,
            'New Material Order Received',
            "{$business->business_name} ordered {$orderQty} {$listing->unit} of '{$listing->name}'. Order #{$orderRef}.",
            'order',
            '/business/transactions?tab=sales',
            'green'
        );

        BusinessNotification::send(
            $business->id,
            $isFree ? 'Free Material Claimed & Receipt Ready' : 'Material Order Placed',
            $isFree
                ? "You claimed {$orderQty} {$listing->unit} of '{$listing->name}' for RM 0.00. Your receipt is ready."
                : "You placed Order #{$orderRef} for {$orderQty} {$listing->unit} of '{$listing->name}'.",
            'order',
            $isFree && $exchange->invoice ? "/business/invoices/{$exchange->invoice->id}" : '/business/transactions?tab=purchases',
            'blue'
        );

        // Sell item with checkout flow -> go to simple Payment screen
        if ($request->boolean('proceed_to_checkout') && $exchange->requiresStripePayment()) {
            return redirect()->route('business.orders.checkout', $exchange)
                ->with('status', "Order {$orderRef} created. Review details and click Pay Now to complete payment via Stripe.");
        }

        // Free item with receipt -> go to transactions history (receipt link available immediately)
        return redirect()->route('business.transactions', ['tab' => 'purchases'])
            ->with('status', $isFree
                ? "Free order #{$orderRef} completed (RM 0.00). Your EcoCycle Receipt is ready to view and download."
                : "Order #{$orderRef} placed. Complete payment to generate your receipt.");
    }

    public function requestPayment(Request $request, MaterialExchange $exchange): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();
        abort_unless($exchange->provider_business_id === $business->id, 403, 'Only the material seller/provider can request payment.');

        if (empty($exchange->invoice_number)) {
            $exchange->invoice_number = 'INV-'.date('Y').'-'.str_pad((string) $exchange->id, 5, '0', STR_PAD_LEFT);
        }

        $exchange->payment_status = 'requested';
        $exchange->payment_requested_at = now();
        $exchange->save();

        // Notify Buyer
        BusinessNotification::send(
            $exchange->requester_business_id,
            'Payment Requested for Material Order',
            "{$business->business_name} has requested payment of RM ".number_format((float) $exchange->total_price, 2)." for Invoice #{$exchange->invoice_number}.",
            '/business/transactions?tab=purchases'
        );

        return back()->with('status', "Payment request and Invoice #{$exchange->invoice_number} sent to buyer.");
    }

    public function markPaid(Request $request, MaterialExchange $exchange): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();
        abort_unless(
            $exchange->provider_business_id === $business->id || $exchange->requester_business_id === $business->id,
            403
        );

        $ref = $request->input('payment_reference') ?: ('FPX-'.strtoupper(bin2hex(random_bytes(4))));

        $alreadyPaid = DB::transaction(function () use ($exchange, $ref): bool {
            $lockedExchange = MaterialExchange::query()
                ->where('id', $exchange->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedExchange->payment_status === 'paid') {
                return true;
            }

            $now = now();
            $lockedExchange->payment_status = 'paid';
            $lockedExchange->payment_reference = $ref;
            $lockedExchange->paid_at = $now;
            $lockedExchange->status = 'completed';
            $lockedExchange->completed_at = $now;
            $lockedExchange->save();

            $payment = Payment::updateOrCreate(
                ['material_exchange_id' => $lockedExchange->id],
                [
                    'buyer_business_id' => $lockedExchange->requester_business_id,
                    'seller_business_id' => $lockedExchange->provider_business_id,
                    'currency' => 'myr',
                    'amount' => $lockedExchange->total_price,
                    'status' => 'paid',
                    'paid_at' => $now,
                ]
            );

            // Deduct purchased stock atomically once
            $lockedListing = Item::query()
                ->where('id', $lockedExchange->item_id)
                ->lockForUpdate()
                ->first();

            if ($lockedListing) {
                $newListingQty = max(0, (float) $lockedListing->quantity - (float) $lockedExchange->quantity);
                $newStatus = $newListingQty <= 0 ? 'sold' : 'active';
                $lockedListing->update([
                    'quantity' => $newListingQty,
                    'status' => $newStatus,
                ]);

                InventoryTransaction::create([
                    'business_id' => $lockedListing->business_id,
                    'inventory_item_id' => $lockedListing->id,
                    'transaction_type' => 'stock_out',
                    'quantity_change' => -(float) $lockedExchange->quantity,
                    'quantity_after' => $newListingQty,
                    'notes' => "Sold via Order #{$lockedExchange->resolvedOrderReference()} (Ref: {$ref})",
                ]);
            }

            $this->invoiceService->generateForOrder($lockedExchange, $payment);

            return false;
        });

        $exchange->refresh();

        if ($alreadyPaid) {
            return back()->with('status', "Order #{$exchange->invoice_number} is already marked as PAID.");
        }

        // Notify counterpart
        $notifyTarget = ($business->id === $exchange->provider_business_id) ? $exchange->requester_business_id : $exchange->provider_business_id;
        BusinessNotification::send(
            $notifyTarget,
            'Payment Confirmed & Exchange Completed',
            "Payment for order #{$exchange->invoice_number} (Ref: {$ref}) has been marked as completed.",
            '/business/transactions'
        );

        return back()->with('status', "Transaction marked as PAID (Ref: {$ref}).");
    }
}
