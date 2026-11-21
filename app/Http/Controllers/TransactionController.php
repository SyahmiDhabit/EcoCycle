<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\MaterialExchange;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\StripePaymentService;
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
        private readonly StripePaymentService $stripeService,
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

        // Automatically sync any pending Stripe checkout sessions for this business
        $this->stripeService->syncPendingStripePaymentsForBusiness($business->id);

        $viewType = $request->input('tab', 'all'); // all, sales, purchases

        $query = MaterialExchange::with(['listing', 'provider', 'requester', 'invoice', 'latestPayment', 'review'])->latest();

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
        abort_if($listing->status !== 'active' || $listing->isPermanentlySoldOut(), 400, 'This item is no longer available on the Marketplace.');

        $listingType = strtolower(trim((string) ($listing->listing_type ?: 'buy')));
        abort_unless(
            in_array($listingType, Item::ALLOWED_LISTING_TYPES, true),
            422,
            'Only Sell, Free, and Lend listing types are supported.'
        );

        $availableQty = (float) $listing->quantity;
        abort_if($availableQty <= 0, 400, 'This item is currently out of stock.');

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01', "max:{$availableQty}"],
            'collection_address' => ['nullable', 'string', 'max:255'],
            'fulfillment_method' => ['nullable', 'in:collection,delivery'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.required' => 'Please enter the quantity you wish to order, claim, or borrow.',
            'quantity.min' => 'Quantity must be at least 0.01.',
            'quantity.max' => "Requested quantity exceeds available stock of {$availableQty} {$listing->unit}.",
        ]);

        $orderQty = (float) $validated['quantity'];
        $isLend = $listingType === 'lend';
        $isFree = ! $isLend && ((bool) $listing->is_free || $listingType === 'free');
        $isNoPayment = $isFree || $isLend;
        $normalizedType = $isLend ? 'lend' : ($isFree ? 'free' : 'buy');

        // Calculate price strictly on the backend (unit price * quantity)
        $unitPrice = $isNoPayment ? 0.00 : round((float) ($listing->price ?? 0), 2);
        $totals = $this->invoiceService->calculateTotals($orderQty, $unitPrice);
        $totalPrice = $isNoPayment ? 0.00 : $totals['total'];

        $orderRef = 'ECO-ORD-'.date('Y').'-'.strtoupper(Str::random(6));
        $initialPaymentStatus = $isNoPayment ? 'free' : 'pending';
        $fulfillmentMethod = (string) ($validated['fulfillment_method'] ?? 'collection');

        $seller = User::find($listing->business_id);
        $sellerName = trim((string) ($seller?->business_name ?: ($seller?->name ?? 'Seller Business')));
        $buyerName = trim((string) ($business->business_name ?: ($business->name ?? 'Buyer Business')));

        $buyerDefaultAddress = trim(implode(', ', array_filter([
            trim((string) ($business->registered_address ?? '')),
            trim(implode(' ', array_filter([trim((string) ($business->postcode ?? '')), trim((string) ($business->city ?? ''))]))),
            trim((string) ($business->state ?? '')),
        ])));
        $sellerPickupAddress = trim((string) ($listing->pickup_address ?? ''));
        if ($sellerPickupAddress === '' && $seller) {
            $sellerPickupAddress = trim(implode(', ', array_filter([
                trim((string) ($seller->registered_address ?? '')),
                trim(implode(' ', array_filter([trim((string) ($seller->postcode ?? '')), trim((string) ($seller->city ?? ''))]))),
                trim((string) ($seller->state ?? '')),
            ])));
        }

        $collectionAddress = trim((string) ($validated['collection_address'] ?? ''));
        if ($collectionAddress === '') {
            $collectionAddress = $fulfillmentMethod === 'delivery'
                ? ($buyerDefaultAddress !== '' ? $buyerDefaultAddress : $sellerPickupAddress)
                : ($sellerPickupAddress !== '' ? $sellerPickupAddress : $buyerDefaultAddress);
        }

        $exchange = DB::transaction(function () use (
            $listing,
            $business,
            $normalizedType,
            $orderQty,
            $unitPrice,
            $totalPrice,
            $orderRef,
            $isFree,
            $isLend,
            $isNoPayment,
            $initialPaymentStatus,
            $sellerName,
            $buyerName,
            $collectionAddress,
            $fulfillmentMethod,
            $validated
        ): MaterialExchange {
            // Lock item row inside transaction to strictly prevent overselling
            $lockedListing = Item::query()
                ->where('id', $listing->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedListing->status !== 'active' || $lockedListing->isPermanentlySoldOut()) {
                throw ValidationException::withMessages([
                    'quantity' => 'This item is no longer available on the Marketplace.',
                ]);
            }

            if ((float) $lockedListing->quantity < $orderQty) {
                throw ValidationException::withMessages([
                    'quantity' => "Requested quantity exceeds available stock of {$lockedListing->quantity} {$lockedListing->unit}.",
                ]);
            }

            $now = now();
            $itemNameSnapshot = (string) ($lockedListing->name ?: ($lockedListing->title ?: "Material #{$lockedListing->id}"));

            $exchange = MaterialExchange::create([
                'invoice_number' => $orderRef,
                'order_reference' => $orderRef,
                'item_id' => $lockedListing->id,
                'item_name' => $itemNameSnapshot,
                'provider_business_id' => $lockedListing->business_id,
                'seller_business_name' => $sellerName,
                'requester_business_id' => $business->id,
                'buyer_business_name' => $buyerName,
                'listing_type' => $normalizedType,
                'quantity' => $orderQty,
                'unit' => $lockedListing->unit,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'collection_address' => $collectionAddress !== '' ? $collectionAddress : null,
                'fulfillment_method' => $fulfillmentMethod,
                'status' => $isNoPayment ? 'confirmed' : 'pending',
                'delivery_status' => $isNoPayment ? 'confirmed' : 'payment_pending',
                'payment_status' => $initialPaymentStatus,
                'payment_reference' => $isLend ? 'LEND' : ($isFree ? 'FREE' : null),
                'paid_at' => $isNoPayment ? $now : null,
                'completed_at' => null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! $isNoPayment) {
                // Sell item: create single pending Payment record; stock is NOT deducted until verified Stripe payment
                Payment::create([
                    'material_exchange_id' => $exchange->id,
                    'buyer_business_id' => $business->id,
                    'seller_business_id' => $lockedListing->business_id,
                    'currency' => 'myr',
                    'amount' => $totalPrice,
                    'status' => 'pending',
                    'paid_at' => null,
                ]);

                $exchange->recordStatusChange(
                    'payment_pending',
                    $business->id,
                    'Order created. Awaiting verified Stripe payment before stock deduction.'
                );
            } else {
                // Free claim or Borrow/Lend: do NOT create a Stripe payment record; deduct/reserve atomically now
                $newQty = max(0, round((float) $lockedListing->quantity - $orderQty, 2));
                if ($isLend) {
                    // Lend items reaching 0 are temporarily inactive until returned (NOT permanently sold out)
                    $newStatus = $newQty <= 0 ? 'inactive' : 'active';
                } else {
                    // Free items reaching 0 are permanently Sold Out
                    $newStatus = $newQty <= 0 ? 'sold' : 'active';
                }

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
                    'notes' => $isLend
                        ? "Reserved for lending via Order #{$orderRef}"
                        : "Free material claimed via Order #{$orderRef}",
                ]);

                $exchange->recordStatusChange(
                    'confirmed',
                    $business->id,
                    $isLend
                        ? 'Borrow request confirmed. Quantity reserved until returned to seller.'
                        : 'Free material claim confirmed. No payment required.'
                );

                // Generate claim / borrow receipt with zero Stripe payment record
                $this->invoiceService->generateForOrder($exchange, null);
            }

            return $exchange;
        });

        // Notifications
        $actionSummary = $isLend
            ? "requested to borrow {$orderQty} {$listing->unit}"
            : ($isFree ? "claimed {$orderQty} {$listing->unit} (Free)" : "ordered {$orderQty} {$listing->unit}");

        BusinessNotification::send(
            $listing->business_id,
            $isLend ? 'New Borrow Request Received' : ($isFree ? 'Free Material Claim Received' : 'New Material Order Received'),
            "{$business->business_name} {$actionSummary} of '{$listing->name}'. Order #{$orderRef}.",
            'order',
            "/business/orders/{$exchange->id}",
            'green'
        );

        BusinessNotification::send(
            $business->id,
            $isLend ? 'Borrow Request Confirmed' : ($isFree ? 'Free Material Claimed & Receipt Ready' : 'Material Order Placed'),
            $isNoPayment
                ? "Your {$exchange->listingTypeLabel()} order #{$orderRef} for {$orderQty} {$listing->unit} of '{$listing->name}' is confirmed (RM 0.00). Track collection/delivery in My Orders."
                : "You placed Order #{$orderRef} for {$orderQty} {$listing->unit} of '{$listing->name}'. Complete payment via Stripe Checkout.",
            'order',
            $isNoPayment ? "/business/orders/{$exchange->id}" : "/business/orders/{$exchange->id}/checkout",
            'blue'
        );

        // Sell item with checkout flow -> go to Checkout Summary screen
        if ($request->boolean('proceed_to_checkout') && $exchange->requiresStripePayment()) {
            return redirect()->route('business.orders.checkout', $exchange)
                ->with('status', "Order {$orderRef} created. Review seller details and click Proceed to Stripe Checkout to pay.");
        }

        if ($isNoPayment) {
            return redirect()->route('business.orders.show', $exchange)
                ->with('status', $isLend
                    ? "Borrow order #{$orderRef} confirmed (RM 0.00). Quantity reserved until returned."
                    : "Free claim #{$orderRef} confirmed (RM 0.00). Your claim receipt is ready and you can track collection/delivery below.");
        }

        return redirect()->route('business.transactions', ['tab' => 'purchases'])
            ->with('status', "Order #{$orderRef} placed. Complete payment to confirm your order and generate your receipt.");
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

        $alreadyPaid = DB::transaction(function () use ($exchange, $ref, $business): bool {
            $lockedExchange = MaterialExchange::query()
                ->where('id', $exchange->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedExchange->payment_status === 'paid') {
                return true;
            }

            // Lock item row and verify sufficient stock before confirming payment
            $lockedListing = Item::query()
                ->where('id', $lockedExchange->item_id)
                ->lockForUpdate()
                ->first();

            if ($lockedListing && (float) $lockedListing->quantity < (float) $lockedExchange->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Insufficient remaining stock ({$lockedListing->quantity} {$lockedListing->unit}) to complete this order.",
                ]);
            }

            $now = now();
            $lockedExchange->payment_status = 'paid';
            $lockedExchange->payment_reference = $ref;
            $lockedExchange->paid_at = $now;
            $lockedExchange->status = 'confirmed';
            $lockedExchange->delivery_status = 'confirmed';
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
            if ($lockedListing) {
                $newListingQty = max(0, round((float) $lockedListing->quantity - (float) $lockedExchange->quantity, 2));
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

            $lockedExchange->recordStatusChange(
                'confirmed',
                $business->id,
                "Payment confirmed (Ref: {$ref}). Order ready for seller fulfillment."
            );

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
