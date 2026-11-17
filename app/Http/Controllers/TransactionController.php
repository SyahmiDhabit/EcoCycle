<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\MaterialExchange;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        abort_if(($listing->reusability ?? 'reusable') === 'not_reusable', 400, 'This item is not available for public exchange.');

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
        $listingType = $listing->listing_type ?: 'buy';
        $isFree = (bool) $listing->is_free || $listingType === 'free';
        $isBorrow = $listingType === 'borrow';

        // Calculate price strictly on the backend (never trust client-submitted totals)
        $unitPrice = $isFree ? 0.00 : round((float) ($listing->price ?? 0), 2);
        $totals = $this->invoiceService->calculateTotals($orderQty, $unitPrice);
        $totalPrice = $isFree ? 0.00 : $totals['total'];

        $orderRef = 'ECO-ORD-'.date('Y').'-'.strtoupper(Str::random(6));
        $invoiceNumber = $isFree ? ('FREE-'.date('Y').'-'.strtoupper(Str::random(6))) : $orderRef;

        $initialPaymentStatus = match (true) {
            $isFree => 'paid',
            $isBorrow => 'borrow_pending',
            default => 'pending_payment',
        };

        $exchange = DB::transaction(function () use ($listing, $business, $listingType, $orderQty, $unitPrice, $totalPrice, $orderRef, $invoiceNumber, $isFree, $initialPaymentStatus, $validated) {
            $exchange = MaterialExchange::create([
                'invoice_number' => $invoiceNumber,
                'order_reference' => $orderRef,
                'item_id' => $listing->id,
                'provider_business_id' => $listing->business_id,
                'requester_business_id' => $business->id,
                'listing_type' => $listingType,
                'quantity' => $orderQty,
                'unit' => $listing->unit,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'status' => $isFree ? 'completed' : 'pending',
                'payment_status' => $initialPaymentStatus,
                'payment_reference' => $isFree ? 'FREE-GRANT' : null,
                'paid_at' => $isFree ? now() : null,
                'completed_at' => $isFree ? now() : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // If free claim, deduct stock immediately without requiring Stripe payment
            if ($isFree) {
                $newQty = max(0, (float) $listing->quantity - $orderQty);
                $newStatus = $newQty <= 0 ? 'sold' : 'active';
                $listing->update([
                    'quantity' => $newQty,
                    'status' => $newStatus,
                ]);

                InventoryTransaction::create([
                    'business_id' => $listing->business_id,
                    'inventory_item_id' => $listing->id,
                    'transaction_type' => 'stock_out',
                    'quantity_change' => -$orderQty,
                    'quantity_after' => $newQty,
                    'notes' => "Free material claimed via Order #{$orderRef}",
                ]);
            }

            return $exchange;
        });

        // Notifications
        BusinessNotification::send(
            $listing->business_id,
            'New Material Order Received',
            "{$business->business_name} ordered {$orderQty} {$listing->unit} of '{$listing->name}'. Order #{$orderRef}.",
            'exchange',
            '/business/transactions?tab=sales',
            'green'
        );

        BusinessNotification::send(
            $business->id,
            'Material Order Placed',
            "You placed Order #{$orderRef} for {$orderQty} {$listing->unit} of '{$listing->name}'.",
            'exchange',
            '/business/transactions?tab=purchases',
            'blue'
        );

        // If buyer clicked Review & Pay Now on a Sell item, take them straight to the Order Review & Stripe Checkout screen
        if ($request->boolean('proceed_to_checkout') && $exchange->requiresStripePayment()) {
            return redirect()->route('business.orders.checkout', $exchange)
                ->with('status', "Order {$orderRef} created. Please review the summary below and click Pay Now to complete payment via Stripe.");
        }

        return redirect()->route('business.transactions', ['tab' => 'purchases'])
            ->with('status', "Order #{$orderRef} successfully placed! You can review details and manage payment below.");
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

        $exchange->payment_status = 'paid';
        $exchange->payment_reference = $ref;
        $exchange->paid_at = now();
        $exchange->status = 'completed';
        $exchange->completed_at = now();
        if (empty($exchange->invoice_number)) {
            $exchange->invoice_number = 'INV-'.date('Y').'-'.str_pad((string) $exchange->id, 5, '0', STR_PAD_LEFT);
        }
        $exchange->save();

        // Generate official invoice idempotently if paid
        $this->invoiceService->generateForOrder($exchange);

        // Automatically deduct purchased quantity from the listing and inventory
        if ($listing = $exchange->listing) {
            $newListingQty = max(0, (float) $listing->quantity - (float) $exchange->quantity);
            $newStatus = $newListingQty <= 0 ? 'sold' : 'active';
            $listing->update([
                'quantity' => $newListingQty,
                'status' => $newStatus,
            ]);

            InventoryTransaction::create([
                'business_id' => $listing->business_id,
                'inventory_item_id' => $listing->id,
                'transaction_type' => 'stock_out',
                'quantity_change' => -(float) $exchange->quantity,
                'quantity_after' => $newListingQty,
                'notes' => "Sold via Order #{$exchange->invoice_number} (Ref: {$ref})",
            ]);
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
