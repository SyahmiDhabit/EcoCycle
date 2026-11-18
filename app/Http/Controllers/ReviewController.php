<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\MarketplaceReview;
use App\Models\MaterialExchange;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReviewController extends Controller
{
    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    /**
     * My Reviews page: Eligible delivered orders to review, Submitted Reviews, and Received Seller Reviews.
     */
    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $business */
        $business = Auth::user();
        if ($business->account_status !== 'approved') {
            return redirect()->route('business.pending');
        }

        $tab = (string) $request->input('tab', 'eligible');
        if (! in_array($tab, ['eligible', 'submitted', 'received'], true)) {
            $tab = 'eligible';
        }

        // Verified delivered/completed/returned buyer orders without a review yet
        $eligibleOrders = MaterialExchange::query()
            ->with(['item', 'provider'])
            ->where('requester_business_id', $business->id)
            ->where('provider_business_id', '!=', $business->id)
            ->whereIn('delivery_status', ['delivered', 'completed', 'returned'])
            ->whereDoesntHave('review')
            ->latest()
            ->get();

        $submittedReviews = MarketplaceReview::query()
            ->with(['seller', 'listing', 'exchange'])
            ->where('reviewer_business_id', $business->id)
            ->latest()
            ->get();

        $receivedReviews = MarketplaceReview::query()
            ->with(['reviewer', 'listing', 'exchange'])
            ->where('seller_business_id', $business->id)
            ->latest()
            ->get();

        $averageSellerRating = $receivedReviews->isNotEmpty()
            ? round((float) $receivedReviews->avg('rating'), 1)
            : 0.0;

        return view('business.reviews.index', [
            'business' => $business,
            'currentTab' => $tab,
            'eligibleOrders' => $eligibleOrders,
            'submittedReviews' => $submittedReviews,
            'receivedReviews' => $receivedReviews,
            'averageSellerRating' => $averageSellerRating,
        ]);
    }

    /**
     * Submit a verified buyer review (1–5 stars + comment) for a delivered/completed/returned order.
     */
    public function store(Request $request, MaterialExchange $exchange): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        // Only the verified buyer of this order may review (strictly prevents unrelated users and self-reviews)
        abort_unless($exchange->requester_business_id === $business->id, 403, 'Only the verified buyer of this order can submit a review.');
        abort_if($exchange->provider_business_id === $business->id, 403, 'You cannot review your own business.');

        if (! $exchange->isReviewable()) {
            return back()->withErrors([
                'review' => 'Reviews can only be submitted after the order has been marked as Delivered, Collected, Completed, or Returned.',
            ]);
        }

        if ($exchange->review()->exists()) {
            return back()->withErrors([
                'review' => 'You have already submitted a review for this order.',
            ]);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'rating.required' => 'Please select a star rating between 1 and 5.',
            'rating.between' => 'Rating must be between 1 and 5 stars.',
            'comment.required' => 'Please share a brief review comment about your experience.',
        ]);

        MarketplaceReview::create([
            'material_exchange_id' => $exchange->id,
            'item_id' => $exchange->item_id,
            'reviewer_business_id' => $business->id,
            'seller_business_id' => $exchange->provider_business_id,
            'rating' => (int) $validated['rating'],
            'comment' => trim((string) $validated['comment']),
        ]);

        $itemName = $exchange->item_name ?: ($exchange->item?->name ?: 'your item');

        BusinessNotification::send(
            $exchange->provider_business_id,
            'New Verified Seller Review Received',
            "{$business->business_name} rated your order #{$exchange->resolvedOrderReference()} ('{$itemName}') {$validated['rating']}/5 stars.",
            'review',
            '/business/reviews?tab=received',
            'green'
        );

        return redirect()->route('business.reviews.index', ['tab' => 'submitted'])
            ->with('status', "Thank you! Your {$validated['rating']}-star review for Order #{$exchange->resolvedOrderReference()} has been submitted.");
    }
}
