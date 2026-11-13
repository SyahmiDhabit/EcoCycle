<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryTransaction;
use App\Models\MaterialExchange;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransactionController extends Controller
{
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

        $query = MaterialExchange::with(['listing', 'provider', 'requester'])->latest();

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

    public function requestPayment(Request $request, MaterialExchange $exchange): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();
        abort_unless($exchange->provider_business_id === $business->id, 403, 'Only the material seller/provider can request payment.');

        if (empty($exchange->invoice_number)) {
            $exchange->invoice_number = 'INV-' . date('Y') . '-' . str_pad((string) $exchange->id, 5, '0', STR_PAD_LEFT);
        }

        $exchange->payment_status = 'requested';
        $exchange->payment_requested_at = now();
        $exchange->save();

        // Notify Buyer
        BusinessNotification::send(
            $exchange->requester_business_id,
            'Payment Requested for Material Order',
            "{$business->business_name} has requested payment of RM " . number_format((float) $exchange->total_price, 2) . " for Invoice #{$exchange->invoice_number}.",
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

        $ref = $request->input('payment_reference') ?: ('FPX-' . strtoupper(bin2hex(random_bytes(4))));

        $exchange->payment_status = 'paid';
        $exchange->payment_reference = $ref;
        $exchange->paid_at = now();
        $exchange->status = 'completed';
        $exchange->completed_at = now();
        if (empty($exchange->invoice_number)) {
            $exchange->invoice_number = 'INV-' . date('Y') . '-' . str_pad((string) $exchange->id, 5, '0', STR_PAD_LEFT);
        }
        $exchange->save();

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
