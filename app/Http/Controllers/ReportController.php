<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\MaterialExchange;
use App\Models\User;
use App\Models\WasteRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $business */
        $business = Auth::user();
        if ($business->account_status !== 'approved') {
            return redirect()->route('business.dashboard');
        }

        $filters = Validator::make($request->query(), [
            'period' => ['nullable', 'in:day,week,month,quarter,year,custom'],
            'from' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d'],
            'to' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $period = $filters->fails() ? 'week' : ($filters->validated()['period'] ?? 'week');
        $today = CarbonImmutable::today();
        [$start, $end] = match ($period) {
            'day' => [$today, $today],
            'month' => [$today->startOfMonth(), $today],
            'quarter' => [$today->startOfMonth()->subMonths(2), $today],
            'year' => [$today->startOfYear(), $today],
            'custom' => [CarbonImmutable::parse($filters->validated()['from']), CarbonImmutable::parse($filters->validated()['to'])],
            default => [$today->startOfWeek(), $today],
        };
        $until = $end->addDay();
        $inPeriod = static fn ($date): bool => $date !== null && $date >= $start && $date < $until;

        $items = Item::where('business_id', $business->id)
            ->get(['id', 'category', 'reusability', 'quantity', 'status', 'created_at']);
        $cohort = $items->filter(fn (Item $item): bool => $inPeriod($item->created_at));
        $listedItems = $items->filter(fn (Item $item): bool => in_array($item->status, ['active', 'inactive', 'sold', 'completed'], true));
        $listedCohort = $listedItems->filter(fn (Item $item): bool => $inPeriod($item->created_at));

        $exchanges = MaterialExchange::where(function ($query) use ($business): void {
            $query->where('provider_business_id', $business->id)->orWhere('requester_business_id', $business->id);
        })->latest()->orderByDesc('id')->get();

        $periodOrders = $exchanges->filter(fn (MaterialExchange $order): bool => $inPeriod($order->created_at));
        $paidOrders = $exchanges->filter(fn (MaterialExchange $order): bool => $order->payment_status === 'paid'
            && $inPeriod($order->paid_at ?? $order->created_at));
        $paidSales = $paidOrders->where('provider_business_id', $business->id);
        $paidPurchases = $paidOrders->where('requester_business_id', $business->id);
        $reusability = [
            'Reusable' => $cohort->where('reusability', 'reusable')->count(),
            'Non-reusable' => $cohort->where('reusability', 'not_reusable')->count(),
        ];
        $listingCategories = $listedCohort->countBy(fn (Item $item): string => trim((string) $item->category) ?: 'Uncategorised')->sortDesc()->all();

        $days = $start->diffInDays($end);
        [$trendInterval, $trendFormat] = match (true) {
            $period === 'day' => ['hour', 'H:i'],
            $days <= 45 => ['day', 'j M Y'],
            $days <= 120 => ['week', 'j M Y'],
            $days <= 730 => ['month', 'M Y'],
            default => ['year', 'Y'],
        };
        $intervalLabel = static fn (MaterialExchange $order): string =>
            CarbonImmutable::instance($order->paid_at ?? $order->created_at)->startOf($trendInterval)->format($trendFormat);
        $salesByInterval = $paidSales->groupBy($intervalLabel);
        $purchasesByInterval = $paidPurchases->groupBy($intervalLabel);
        $salesTrend = [];
        $purchaseTrend = [];
        for ($date = $start->startOf($trendInterval); $date < $until; $date = $date->addUnit($trendInterval, 1)) {
            $label = $date->format($trendFormat);
            $sales = $salesByInterval->get($label, collect());
            $salesTrend[$label] = (float) $sales->sum('total_price');
            $purchaseTrend[$label] = (float) $purchasesByInterval->get($label, collect())->sum('total_price');
        }

        $wasteRecords = WasteRecord::where('business_id', $business->id)
            ->where('recorded_at', '>=', $start)->where('recorded_at', '<', $until)
            ->latest('recorded_at')->orderByDesc('id')->get();

        return view('business.reports.index', [
            'business' => $business,
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'filterErrors' => $filters->errors(),
            'totalItems' => $cohort->count(),
            'totalTransactions' => $periodOrders->count(),
            'totalSalesRevenue' => (float) $paidSales->sum('total_price'),
            'salesTrend' => $salesTrend,
            'purchaseTrend' => $purchaseTrend,
            'trendInterval' => $trendInterval,
            'totalSalesOrders' => $paidSales->count(),
            'totalPurchasesSpent' => (float) $paidPurchases->sum('total_price'),
            'totalPurchasesOrders' => $paidPurchases->count(),
            'reusableMaterials' => $reusability['Reusable'],
            'listingCategories' => $listingCategories,
            'reusability' => $reusability,
            'wasteCategories' => $wasteRecords->countBy(fn (WasteRecord $record): string => trim((string) $record->waste_type) ?: 'Uncategorised')->sortDesc()->all(),
            'wasteStatuses' => $wasteRecords->countBy(fn (WasteRecord $record): string => $record->status
                ? ucfirst(str_replace('_', ' ', $record->status))
                : 'Unspecified')->sortDesc()->all(),
            'recentTransactions' => $periodOrders->take(8)->load(['listing', 'provider', 'requester']),
            'recentWasteLogs' => $wasteRecords->take(8),
        ]);
    }
}
