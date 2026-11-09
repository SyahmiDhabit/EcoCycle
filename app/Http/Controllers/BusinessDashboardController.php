<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BusinessDashboardController extends Controller
{
    public function __invoke(): View
    {
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return view('business.verification', ['business' => $business]);
        }

        $trackedItemsCount = $business->inventoryItems()->count();
        $materialsListedCount = $business->marketplaceListings()->where('status', 'active')->count();
        $wasteDivertedKg = (float) $business->wasteRecords()->where('is_diverted', true)->sum('quantity');
        $exchangeImpactRm = (float) $business->wasteRecords()->sum('cost_or_savings')
            + (float) $business->materialExchangesAsProvider()->where('status', 'completed')->sum('total_price');

        $recentInventory = $business->inventoryItems()
            ->latest()
            ->take(5)
            ->get();

        $recentActivity = $business->inventoryTransactions()
            ->with('inventoryItem')
            ->latest()
            ->take(5)
            ->get();

        return view('business.dashboard', [
            'business' => $business,
            'trackedItemsCount' => $trackedItemsCount,
            'materialsListedCount' => $materialsListedCount,
            'wasteDivertedKg' => $wasteDivertedKg,
            'exchangeImpactRm' => $exchangeImpactRm,
            'recentInventory' => $recentInventory,
            'recentActivity' => $recentActivity,
        ]);
    }
}
