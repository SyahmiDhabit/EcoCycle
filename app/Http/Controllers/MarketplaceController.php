<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $business */
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return view('business.verification', ['business' => $business]);
        }

        // Query real marketplace listings directly from database
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $location = trim((string) $request->query('location', ''));

        $query = MarketplaceListing::query()
            ->with('business')
            ->where('status', 'active');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($location !== '') {
            $query->where(function ($q) use ($location) {
                $q->where('location_state', 'like', "%{$location}%")
                  ->orWhere('location_city', 'like', "%{$location}%");
            });
        }

        $listings = $query->latest()->get();

        // Get distinct categories and locations from database
        $categories = MarketplaceListing::query()
            ->where('status', 'active')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        $locations = MarketplaceListing::query()
            ->where('status', 'active')
            ->distinct()
            ->pluck('location_state')
            ->filter()
            ->values();

        return view('business.marketplace.index', [
            'business' => $business,
            'listings' => $listings,
            'categories' => $categories,
            'locations' => $locations,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedLocation' => $location,
        ]);
    }
}
