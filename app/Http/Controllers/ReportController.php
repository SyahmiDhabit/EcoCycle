<?php

namespace App\Http\Controllers;

use App\Models\MaterialExchange;
use App\Models\User;
use App\Models\WasteRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
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

        $salesExchanges = MaterialExchange::where('provider_business_id', $business->id)
            ->where('payment_status', 'paid')->get(['total_price']);
        $purchaseExchanges = MaterialExchange::where('requester_business_id', $business->id)
            ->where('payment_status', 'paid')->get(['total_price']);

        $wasteRecords = WasteRecord::where('business_id', $business->id)->get(['waste_type', 'status']);
        $wasteCategories = $wasteRecords->countBy(fn (WasteRecord $record): string => trim((string) $record->waste_type) ?: 'Uncategorised')
            ->sortDesc()->all();
        $wasteStatuses = $wasteRecords->countBy(fn (WasteRecord $record): string => $record->status
            ? ucfirst(str_replace('_', ' ', $record->status))
            : 'Unspecified')->sortDesc()->all();

        // Recent transaction and waste activity logs for printable audit report
        $recentTransactions = MaterialExchange::where(function ($q) use ($business) {
            $q->where('provider_business_id', $business->id)
                ->orWhere('requester_business_id', $business->id);
        })->with(['listing', 'provider', 'requester'])->latest()->take(8)->get();

        $recentWasteLogs = WasteRecord::where('business_id', $business->id)->latest('recorded_at')->take(8)->get();

        return view('business.reports.index', [
            'business' => $business,
            'totalSalesRevenue' => (float) $salesExchanges->sum('total_price'),
            'totalSalesOrders' => $salesExchanges->count(),
            'totalPurchasesSpent' => (float) $purchaseExchanges->sum('total_price'),
            'totalPurchasesOrders' => $purchaseExchanges->count(),
            'wasteCategories' => $wasteCategories,
            'wasteStatuses' => $wasteStatuses,
            'recentTransactions' => $recentTransactions,
            'recentWasteLogs' => $recentWasteLogs,
        ]);
    }
}
