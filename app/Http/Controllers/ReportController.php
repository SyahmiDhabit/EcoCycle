<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
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

        // 1. Financial Analytics
        $salesExchanges = MaterialExchange::where('provider_business_id', $business->id)->get();
        $totalSalesRevenue = (float) $salesExchanges->where('payment_status', 'paid')->sum('total_price');
        $totalSalesOrders = $salesExchanges->count();

        $purchaseExchanges = MaterialExchange::where('requester_business_id', $business->id)->get();
        $totalPurchasesSpent = (float) $purchaseExchanges->where('payment_status', 'paid')->sum('total_price');
        $totalPurchasesOrders = $purchaseExchanges->count();

        $netProfitOrSavings = $totalSalesRevenue - $totalPurchasesSpent;

        // 2. Circular Materials & Wastage Analytics
        $allWasteRecords = WasteRecord::where('business_id', $business->id)->get();
        $notReusableScrapTotalKg = (float) $allWasteRecords->sum('quantity');

        $scrapDivertedKg = (float) $allWasteRecords->filter(function ($r) {
            return $r->is_diverted || in_array($r->status, ['sent_to_facility', 'recycled', 'composted']);
        })->sum('quantity');

        $scrapInStorageKg = (float) $allWasteRecords->where('status', 'in_storage')->sum('quantity');

        $reusableItems = InventoryItem::where('business_id', $business->id)
            ->where('reusability', 'reusable')
            ->get();
        $reusableStockTotal = (float) $reusableItems->sum('quantity');

        // Total Landfill Diversion (kg) = Reusable circulated + Not-reusable sent to recovery facilities
        $totalDivertedFromLandfillKg = $reusableStockTotal + $scrapDivertedKg;

        // Environmental Metrics: 1 kg diverted material = approx. 1.82 kg CO2 avoided
        $estimatedCo2AvoidedKg = round($totalDivertedFromLandfillKg * 1.82, 1);

        // Circularity Index % = Diverted / (Diverted + Landfilled/Pending)
        $circularityRate = ($notReusableScrapTotalKg + $reusableStockTotal) > 0 ?
            round(($totalDivertedFromLandfillKg / ($notReusableScrapTotalKg + $reusableStockTotal)) * 100, 1) : 100.0;

        // Category breakdown for reporting
        $categoryBreakdown = [];
        foreach ($allWasteRecords as $w) {
            $cat = $w->waste_type ?: 'General Scrap';
            if (! isset($categoryBreakdown[$cat])) {
                $categoryBreakdown[$cat] = 0;
            }
            $categoryBreakdown[$cat] += (float) $w->quantity;
        }

        // Recent transaction and waste activity logs for printable audit report
        $recentTransactions = MaterialExchange::where(function ($q) use ($business) {
            $q->where('provider_business_id', $business->id)
                ->orWhere('requester_business_id', $business->id);
        })->with(['listing', 'provider', 'requester'])->latest()->take(8)->get();

        $recentWasteLogs = WasteRecord::where('business_id', $business->id)->latest('recorded_at')->take(8)->get();

        return view('business.reports.index', [
            'business' => $business,
            'totalSalesRevenue' => $totalSalesRevenue,
            'totalSalesOrders' => $totalSalesOrders,
            'totalPurchasesSpent' => $totalPurchasesSpent,
            'totalPurchasesOrders' => $totalPurchasesOrders,
            'netProfitOrSavings' => $netProfitOrSavings,
            'notReusableScrapTotalKg' => $notReusableScrapTotalKg,
            'scrapDivertedKg' => $scrapDivertedKg,
            'scrapInStorageKg' => $scrapInStorageKg,
            'reusableStockTotal' => $reusableStockTotal,
            'totalDivertedFromLandfillKg' => $totalDivertedFromLandfillKg,
            'estimatedCo2AvoidedKg' => $estimatedCo2AvoidedKg,
            'circularityRate' => $circularityRate,
            'categoryBreakdown' => $categoryBreakdown,
            'recentTransactions' => $recentTransactions,
            'recentWasteLogs' => $recentWasteLogs,
        ]);
    }
}
