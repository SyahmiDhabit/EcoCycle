<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceReview;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MyListingController extends Controller
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

        $statusTab = $request->input('status', 'all'); // all, active, sold, borrowed

        $query = MarketplaceListing::where('business_id', $business->id)->with('reviews.reviewer')->latest();

        if ($statusTab === 'active') {
            $query->where('status', 'active');
        } elseif ($statusTab === 'sold') {
            $query->where('status', 'sold');
        } elseif ($statusTab === 'borrowed') {
            $query->where('listing_type', 'borrow');
        }

        $myListings = $query->paginate(12)->withQueryString();

        // All reviews received across all my listings
        $allReviews = MarketplaceReview::where('seller_business_id', $business->id)
            ->with(['reviewer', 'listing'])
            ->latest()
            ->get();

        $avgRating = $allReviews->isNotEmpty() ? round($allReviews->avg('rating'), 1) : 5.0;
        $totalReviewCount = $allReviews->count();

        // Counts
        $allListings = MarketplaceListing::where('business_id', $business->id)->get();
        $totalActive = $allListings->where('status', 'active')->count();
        $totalSold = $allListings->where('status', 'sold')->count();
        $totalBorrowed = $allListings->where('listing_type', 'borrow')->count();

        return view('business.listings.index', [
            'business' => $business,
            'listings' => $myListings,
            'currentTab' => $statusTab,
            'reviews' => $allReviews,
            'avgRating' => $avgRating,
            'totalReviewCount' => $totalReviewCount,
            'totalActive' => $totalActive,
            'totalSold' => $totalSold,
            'totalBorrowed' => $totalBorrowed,
        ]);
    }

    public function updateStatus(Request $request, MarketplaceListing $listing): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();
        abort_unless($listing->business_id === $business->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:active,sold,inactive'],
        ]);

        $listing->status = $validated['status'];
        $listing->save();

        return back()->with('status', "Listing status updated to: " . ucfirst($validated['status']));
    }

    public function storeReview(Request $request, MarketplaceListing $listing): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();
        abort_unless($listing->business_id !== $business->id, 400, 'You cannot review your own listing.');

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
            'exchange_type' => ['nullable', 'string', 'max:30'],
        ]);

        MarketplaceReview::create([
            'listing_id' => $listing->id,
            'seller_business_id' => $listing->business_id,
            'reviewer_business_id' => $business->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'exchange_type' => $validated['exchange_type'] ?? ($listing->listing_type ?: 'buy'),
        ]);

        BusinessNotification::send(
            $listing->business_id,
            'New Customer Review Received',
            "{$business->business_name} left a {$validated['rating']}-star feedback on '{$listing->title}'.",
            '/business/my-listings'
        );

        return back()->with('status', 'Thank you! Your feedback has been posted.');
    }
}
