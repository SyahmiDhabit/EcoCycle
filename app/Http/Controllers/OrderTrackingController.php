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
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    /**
     * My Orders page: Buyer purchases/claims/borrows and Seller fulfillment orders with delivery tracking.
     */
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $business */
        $business = Auth::user();
        if ($business->account_status !== 'approved') {
            return redirect()->route('business.pending');
        }

        $tab = (string) $request->input('tab', 'purchases');
        if (! in_array($tab, ['purchases', 'sales', 'all'], true)) {
            $tab = 'purchases';
        }

        $deliveryFilter = trim((string) $request->input('delivery_status', ''));

        $query = MaterialExchange::query()
            ->with(['item', 'provider', 'requester', 'invoice', 'latestPayment', 'review'])
            ->latest();

        if ($tab === 'sales') {
            $query->where('provider_business_id', $business->id);
        } elseif ($tab === 'purchases') {
            $query->where('requester_business_id', $business->id);
        } else {
            $query->where(function ($q) use ($business): void {
                $q->where('provider_business_id', $business->id)
                    ->orWhere('requester_business_id', $business->id);
            });
        }

        if ($deliveryFilter !== '' && array_key_exists($deliveryFilter, MaterialExchange::DELIVERY_STATUS_LABELS)) {
            $query->where('delivery_status', $deliveryFilter);
        }

        $orders = $query->paginate(15)->withQueryString();

        $purchasesCount = MaterialExchange::where('requester_business_id', $business->id)->count();
        $salesCount = MaterialExchange::where('provider_business_id', $business->id)->count();
        $inProgressCount = MaterialExchange::query()
            ->where(function ($q) use ($business): void {
                $q->where('provider_business_id', $business->id)
                    ->orWhere('requester_business_id', $business->id);
            })
            ->whereIn('delivery_status', ['confirmed', 'preparing', 'ready_for_collection', 'in_transit'])
            ->count();
        $deliveredCount = MaterialExchange::query()
            ->where(function ($q) use ($business): void {
                $q->where('provider_business_id', $business->id)
                    ->orWhere('requester_business_id', $business->id);
            })
            ->whereIn('delivery_status', ['delivered', 'completed', 'returned'])
            ->count();

        return view('business.orders.index', [
            'business' => $business,
            'orders' => $orders,
            'currentTab' => $tab,
            'selectedDeliveryStatus' => $deliveryFilter,
            'purchasesCount' => $purchasesCount,
            'salesCount' => $salesCount,
            'inProgressCount' => $inProgressCount,
            'deliveredCount' => $deliveredCount,
        ]);
    }

    /**
     * Order & Delivery Tracking Detail page (strictly accessible to Buyer or Seller only).
     */
    public function show(MaterialExchange $exchange): View
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        abort_unless(
            $exchange->provider_business_id === $business->id || $exchange->requester_business_id === $business->id,
            403,
            'You are not authorized to view this order.'
        );

        $exchange->load(['item', 'provider', 'requester', 'invoice', 'latestPayment', 'statusHistory.updatedBy', 'review']);

        $isSeller = $exchange->provider_business_id === $business->id;
        $isBuyer = $exchange->requester_business_id === $business->id;
        $sellerAddress = $this->invoiceService->resolveSellerAddress($exchange);
        $pickupAddress = $this->invoiceService->resolvePickupAddress($exchange);

        $canBuyerConfirmDelivery = $isBuyer
            && in_array($exchange->delivery_status ?: 'payment_pending', ['ready_for_collection', 'in_transit', 'delivered'], true)
            && ($exchange->delivery_status !== 'completed');

        $canSellerMarkReturned = $isSeller
            && $exchange->resolvedListingType() === 'lend'
            && $exchange->borrowed_returned_at === null
            && in_array($exchange->delivery_status, ['confirmed', 'preparing', 'ready_for_collection', 'in_transit', 'delivered', 'completed'], true);

        return view('business.orders.show', [
            'business' => $business,
            'exchange' => $exchange,
            'isSeller' => $isSeller,
            'isBuyer' => $isBuyer,
            'sellerAddress' => $sellerAddress,
            'pickupAddress' => $pickupAddress,
            'allowedSellerStatuses' => $exchange->allowedNextStatusesForSeller(),
            'canBuyerConfirmDelivery' => $canBuyerConfirmDelivery,
            'canSellerMarkReturned' => $canSellerMarkReturned,
        ]);
    }

    /**
     * Update delivery/collection status step-by-step and append to order_status_history.
     */
    public function updateStatus(Request $request, MaterialExchange $exchange): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $isSeller = $exchange->provider_business_id === $business->id;
        $isBuyer = $exchange->requester_business_id === $business->id;

        abort_unless($isSeller || $isBuyer, 403, 'You are not authorized to update this order status.');

        $validated = $request->validate([
            'delivery_status' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $targetStatus = trim((string) $validated['delivery_status']);
        $note = trim((string) ($validated['note'] ?? ''));

        // Strictly block unpaid Sell orders from advancing to fulfillment/delivery before payment is verified
        if ($exchange->requiresStripePayment() && $exchange->payment_status !== 'paid' && $targetStatus !== 'cancelled') {
            return back()->withErrors([
                'delivery_status' => 'Cannot update delivery status before Stripe payment has been verified.',
            ]);
        }

        if ($isSeller) {
            $allowed = $exchange->allowedNextStatusesForSeller();
            if (! in_array($targetStatus, $allowed, true)) {
                return back()->withErrors([
                    'delivery_status' => 'Invalid delivery status transition from '.MaterialExchange::formatDeliveryStatus($exchange->delivery_status).'.',
                ]);
            }
        } else {
            // Buyer can only confirm receipt/delivery once order is ready for collection, in transit, or delivered
            $current = $exchange->delivery_status ?: 'payment_pending';
            $buyerAllowed = match ($current) {
                'ready_for_collection', 'in_transit' => ['delivered', 'completed'],
                'delivered' => ['completed'],
                default => [],
            };

            if (! in_array($targetStatus, $buyerAllowed, true)) {
                return back()->withErrors([
                    'delivery_status' => 'You can confirm delivery/collection once the seller marks the order as Ready for Collection, In Transit, or Delivered.',
                ]);
            }
        }

        DB::transaction(function () use ($exchange, $business, $targetStatus, $note, $isBuyer): void {
            $locked = MaterialExchange::query()
                ->where('id', $exchange->id)
                ->lockForUpdate()
                ->firstOrFail();

            $updates = [
                'delivery_status' => $targetStatus,
            ];

            if (in_array($targetStatus, ['delivered', 'completed'], true)) {
                $updates['status'] = 'completed';
                if (! $locked->completed_at) {
                    $updates['completed_at'] = now();
                }
            } elseif ($targetStatus === 'cancelled') {
                $updates['status'] = 'cancelled';
            }

            $locked->update($updates);

            $statusLabel = MaterialExchange::formatDeliveryStatus($targetStatus);
            $defaultNote = $isBuyer
                ? "Buyer confirmed order status as {$statusLabel}."
                : "Seller updated delivery status to {$statusLabel}.";

            $locked->recordStatusChange(
                $targetStatus,
                $business->id,
                $note !== '' ? $note : $defaultNote
            );
        });

        $exchange->refresh();
        $label = $exchange->deliveryStatusLabel();
        $notifyBusinessId = $isSeller ? $exchange->requester_business_id : $exchange->provider_business_id;

        BusinessNotification::send(
            $notifyBusinessId,
            "Order Status Updated: {$label}",
            "Order #{$exchange->resolvedOrderReference()} ('{$exchange->item_name}') status is now {$label}.",
            'order',
            "/business/orders/{$exchange->id}",
            in_array($targetStatus, ['delivered', 'completed'], true) ? 'green' : 'blue'
        );

        return back()->with('status', "Order #{$exchange->resolvedOrderReference()} updated to {$label}.");
    }

    /**
     * Mark a borrowed (Lend) item as returned and atomically restore the reserved quantity back to available stock.
     */
    public function markReturned(Request $request, MaterialExchange $exchange): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        abort_unless($exchange->provider_business_id === $business->id, 403, 'Only the lending seller can confirm item return.');
        abort_unless($exchange->resolvedListingType() === 'lend', 422, 'Return confirmation is only applicable to Lend orders.');

        $note = trim((string) $request->input('note', ''));

        $alreadyReturned = DB::transaction(function () use ($exchange, $business, $note): bool {
            $lockedExchange = MaterialExchange::query()
                ->where('id', $exchange->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedExchange->borrowed_returned_at !== null) {
                return true;
            }

            $now = now();
            $lockedExchange->update([
                'borrowed_returned_at' => $now,
                'delivery_status' => 'returned',
                'status' => 'completed',
                'completed_at' => $lockedExchange->completed_at ?: $now,
            ]);

            // Restore reserved quantity back to the lending item atomically
            $lockedItem = Item::query()
                ->where('id', $lockedExchange->item_id)
                ->lockForUpdate()
                ->first();

            if ($lockedItem) {
                $restoredQty = round((float) $lockedItem->quantity + (float) $lockedExchange->quantity, 2);
                if ($lockedItem->original_quantity !== null && (float) $lockedItem->original_quantity > 0) {
                    $restoredQty = min($restoredQty, (float) $lockedItem->original_quantity);
                }

                $lockedItem->update([
                    'quantity' => $restoredQty,
                    'status' => $restoredQty > 0 ? 'active' : $lockedItem->status,
                ]);

                InventoryTransaction::create([
                    'business_id' => $lockedItem->business_id,
                    'inventory_item_id' => $lockedItem->id,
                    'transaction_type' => 'stock_in',
                    'quantity_change' => (float) $lockedExchange->quantity,
                    'quantity_after' => $restoredQty,
                    'notes' => "Borrowed stock returned via Order #{$lockedExchange->resolvedOrderReference()}",
                ]);
            }

            $lockedExchange->recordStatusChange(
                'returned',
                $business->id,
                $note !== '' ? $note : 'Seller confirmed borrowed item returned. Quantity restored to available stock.'
            );

            return false;
        });

        if ($alreadyReturned) {
            return back()->with('status', "Order #{$exchange->resolvedOrderReference()} was already marked as Returned.");
        }

        BusinessNotification::send(
            $exchange->requester_business_id,
            'Borrowed Item Return Confirmed',
            "{$business->business_name} confirmed receipt of your returned item for Order #{$exchange->resolvedOrderReference()}.",
            'order',
            "/business/orders/{$exchange->id}",
            'green'
        );

        return back()->with('status', "Borrowed item for Order #{$exchange->resolvedOrderReference()} marked as Returned and stock restored.");
    }
}
