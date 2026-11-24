<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceReview;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketplaceController extends Controller
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
            return redirect()->route('business.pending');
        }

        // Query explicitly published marketplace listings only (do NOT auto-publish inventory items)
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $location = trim((string) $request->query('location', ''));
        $listingType = trim((string) $request->query('listing_type', ''));
        $reusability = trim((string) $request->query('reusability', ''));
        $myListingsOnly = $request->boolean('my_listings');

        // Determine current tab / category
        $tab = $request->query('tab');
        if ($myListingsOnly) {
            $tab = 'my_listings';
        } elseif ($tab === 'non_reusable' || $reusability === 'not_reusable') {
            $tab = 'non_reusable';
        } else {
            $tab = 'reusable';
        }

        $query = MarketplaceListing::query()
            ->with('business')
            ->where('status', 'active')
            ->where('quantity', '>', 0);

        if ($tab === 'my_listings') {
            $query->where('business_id', $business->id);
            if ($reusability !== '') {
                $query->where('reusability', $reusability);
            }
        } elseif ($tab === 'non_reusable') {
            // Dedicated Non-Reusable Category (Scrap, Cutoffs, Circular Recovery - excluded from normal reusable market)
            $query->where('reusability', 'not_reusable');
        } else {
            // Normal Reusable Marketplace
            $query->where('reusability', 'reusable');
        }

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

        if ($listingType !== '' && in_array($listingType, Item::ALLOWED_LISTING_TYPES, true)) {
            if ($listingType === 'free') {
                $query->where(function ($q) {
                    $q->where('listing_type', 'free')->orWhere(function ($q2) {
                        $q2->where('is_free', true)->where('listing_type', '!=', 'lend');
                    });
                });
            } else {
                $query->where('listing_type', $listingType);
            }
        }

        if ($reusability !== '' && $tab === 'my_listings') {
            $query->where('reusability', $reusability);
        }

        $listings = $query->latest()->get();

        // Aggregate verified seller ratings for displayed Marketplace cards
        $sellerIds = $listings->pluck('business_id')->unique()->filter()->values();
        $sellerRatings = [];
        if ($sellerIds->isNotEmpty()) {
            $ratingRows = MarketplaceReview::query()
                ->select('seller_business_id', DB::raw('AVG(rating) as avg_rating'), DB::raw('COUNT(*) as review_count'))
                ->whereIn('seller_business_id', $sellerIds)
                ->groupBy('seller_business_id')
                ->get();

            foreach ($ratingRows as $row) {
                $sellerRatings[(int) $row->seller_business_id] = [
                    'avg' => round((float) $row->avg_rating, 1),
                    'count' => (int) $row->review_count,
                ];
            }
        }

        // Counts for tabs (published marketplace listings only)
        $reusableCount = MarketplaceListing::query()
            ->where('status', 'active')
            ->where('quantity', '>', 0)
            ->where('reusability', 'reusable')
            ->count();

        $nonReusableCount = MarketplaceListing::query()
            ->where('status', 'active')
            ->where('quantity', '>', 0)
            ->where('reusability', 'not_reusable')
            ->count();

        $myListingsCount = $business->marketplaceListings()
            ->where('status', 'active')
            ->where('quantity', '>', 0)
            ->count();

        // Get distinct categories and locations for the current category context
        $catLocationScope = MarketplaceListing::query()
            ->where('status', 'active')
            ->where('quantity', '>', 0);

        if ($tab === 'non_reusable') {
            $catLocationScope->where('reusability', 'not_reusable');
        } elseif ($tab === 'reusable') {
            $catLocationScope->where('reusability', 'reusable');
        }

        $categories = (clone $catLocationScope)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        $locations = (clone $catLocationScope)
            ->distinct()
            ->pluck('location_state')
            ->filter()
            ->values();

        // User's reusable inventory items available to publish
        $myInventoryItems = $business->inventoryItems()
            ->where('status', '!=', 'out_of_stock')
            ->where('quantity', '>', 0)
            ->where('reusability', 'reusable')
            ->orderBy('name')
            ->get();

        return view('business.marketplace.index', [
            'business' => $business,
            'listings' => $listings,
            'sellerRatings' => $sellerRatings,
            'categories' => $categories,
            'locations' => $locations,
            'myInventoryItems' => $myInventoryItems,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedLocation' => $location,
            'selectedListingType' => $listingType,
            'selectedReusability' => $reusability,
            'myListingsOnly' => $myListingsOnly,
            'currentTab' => $tab,
            'reusableCount' => $reusableCount,
            'nonReusableCount' => $nonReusableCount,
            'myListingsCount' => $myListingsCount,
        ]);
    }

    public function sellerReviews(MarketplaceListing $listing): JsonResponse
    {
        abort_unless($listing->status === 'active' && $listing->quantity > 0, 404);

        $reviews = MarketplaceReview::query()
            ->with('reviewer:id,business_name')
            ->where('seller_business_id', $listing->business_id)
            ->latest()
            ->orderByDesc('id')
            ->simplePaginate(10)
            ->through(static fn (MarketplaceReview $review): array => [
                'reviewer' => $review->reviewer?->business_name ?: 'EcoCycle business',
                'rating' => $review->rating,
                'comment' => $review->comment,
                'date' => $review->created_at?->format('j M Y'),
            ]);

        return response()->json([
            'reviews' => $reviews->items(),
            'next' => $reviews->nextPageUrl(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $listingTypeInput = (string) $request->input('listing_type', 'buy');
        $priceRules = $listingTypeInput === 'buy'
            ? ['required', 'numeric', 'gt:0']
            : ['nullable', 'numeric', 'min:0'];

        $validated = $request->validate([
            'inventory_item_id' => ['nullable', 'exists:item,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', Rule::in(Item::allowedUnitKeys())],
            'listing_type' => ['required', 'in:buy,free,lend'],
            'reusability' => ['required', 'in:reusable'],
            'price' => $priceRules,
            'condition' => ['nullable', Rule::in(Item::ALLOWED_CONDITIONS)],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'unit.in' => 'Please select a valid Unit of Measurement from the dropdown.',
            'condition.in' => 'Please select a valid Material Condition (New, Damaged, or Used).',
            'price.required' => 'Sell listings must have a valid Price (RM) greater than RM 0.00.',
            'price.gt' => 'Sell listings must have a valid Price (RM) greater than RM 0.00.',
            'images.max' => 'You can upload a maximum of 3 photos.',
            'reusability.in' => 'Only reusable materials can be listed on the marketplace. Non-reusable items remain saved in your internal inventory only.',
        ]);

        $reusability = $validated['reusability'] ?? 'reusable';
        $listingType = $validated['listing_type'];
        $isFree = in_array($listingType, ['free', 'lend'], true);
        $price = $isFree ? 0.00 : round((float) $validated['price'], 2);
        $description = ! empty($validated['description']) ? $validated['description'] : 'Reusable material available on EcoCycle Marketplace.';
        $condition = ! empty($validated['condition']) ? $validated['condition'] : 'Used';
        $pickupAddressInput = trim((string) ($validated['pickup_address'] ?? ''));

        // Handle 1 to 3 uploaded images
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $disk = Storage::disk('public');
                foreach (array_slice($files, 0, 3) as $file) {
                    if ($file && $file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'listing_'.Str::random(24).'.'.$ext;
                        $disk->putFileAs('listing-images', $file, $filename);
                        $uploadedImages[] = '/listing-images/'.$filename;
                    }
                }
            }
        }

        // If inventory item is specified, ensure it belongs to the user, is reusable, and is not permanently Sold Out
        $inventoryItemId = $validated['inventory_item_id'] ?? null;
        if ($inventoryItemId) {
            $listing = MarketplaceListing::find($inventoryItemId);
            abort_unless($listing && $listing->business_id === $business->id, 403);
            if ($listing->isPermanentlySoldOut()) {
                return back()->withErrors([
                    'quantity' => 'This item is permanently Sold Out and cannot be reactivated. Please add a new inventory item submission for new surplus stock.',
                ]);
            }
            if ($listing->reusability === 'not_reusable') {
                return back()->with('error', 'This item is marked as Not Reusable (Scrap). Only reusable materials can be listed on the public marketplace.');
            }
            if (empty($uploadedImages)) {
                $uploadedImages = $listing->getAllImages();
            }
            $listing->update([
                'title' => $validated['title'],
                'name' => $validated['title'],
                'description' => $description,
                'category' => $validated['category'],
                'quantity' => (float) $validated['quantity'],
                'original_quantity' => $listing->original_quantity ?? (float) $validated['quantity'],
                'unit' => $validated['unit'],
                'listing_type' => $listingType,
                'reusability' => $reusability,
                'price' => $price,
                'is_free' => $isFree,
                'condition' => $condition,
                'pickup_address' => $pickupAddressInput !== '' ? $pickupAddressInput : $listing->pickup_address,
                'status' => 'active',
                'image_path' => $uploadedImages[0] ?? $listing->image_path,
                'image_1' => $uploadedImages[0] ?? $listing->image_1,
                'image_2' => $uploadedImages[1] ?? $listing->image_2,
                'image_3' => $uploadedImages[2] ?? $listing->image_3,
                'images' => ! empty($uploadedImages) ? $uploadedImages : $listing->images,
            ]);
        } else {
            $defaultPickup = trim(implode(', ', array_filter([
                $business->registered_address,
                trim(implode(' ', array_filter([$business->postcode, $business->city ?: 'Kuala Lumpur']))),
                $business->state ?: 'Selangor',
            ])));

            $listing = MarketplaceListing::create([
                'business_id' => $business->id,
                'title' => $validated['title'],
                'name' => $validated['title'],
                'description' => $description,
                'category' => $validated['category'],
                'quantity' => (float) $validated['quantity'],
                'original_quantity' => (float) $validated['quantity'],
                'unit' => $validated['unit'],
                'listing_type' => $listingType,
                'reusability' => $reusability,
                'price' => $price,
                'is_free' => $isFree,
                'condition' => $condition,
                'image_path' => $uploadedImages[0] ?? null,
                'image_1' => $uploadedImages[0] ?? null,
                'image_2' => $uploadedImages[1] ?? null,
                'image_3' => $uploadedImages[2] ?? null,
                'images' => $uploadedImages,
                'location_city' => $business->city ?: 'Kuala Lumpur',
                'location_state' => $business->state ?: 'Selangor',
                'pickup_address' => $pickupAddressInput !== '' ? $pickupAddressInput : ($defaultPickup !== '' ? $defaultPickup : null),
                'status' => 'active',
            ]);

            if ((float) $validated['quantity'] > 0) {
                InventoryTransaction::create([
                    'business_id' => $business->id,
                    'inventory_item_id' => $listing->id,
                    'transaction_type' => 'stock_in',
                    'quantity_change' => (float) $validated['quantity'],
                    'quantity_after' => (float) $validated['quantity'],
                    'notes' => 'Created via Marketplace Listing',
                ]);
            }
        }

        $typeLabel = match ($listingType) {
            'free' => 'for Free Claim',
            'lend' => 'for Lending',
            default => 'for Sale',
        };

        BusinessNotification::send(
            $business->id,
            'Material Listed on Marketplace',
            "Your listing '{$listing->title}' is now live {$typeLabel} ({$validated['reusability']}).",
            'marketplace_listing',
            '/business/marketplace',
            'green'
        );

        return redirect()->route('business.marketplace')->with('status', "Material '{$listing->title}' successfully published to the marketplace {$typeLabel}!");
    }

    public function destroy(MarketplaceListing $listing): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($listing->business_id === Auth::id(), 403);

        $disk = Storage::disk('public');
        foreach ($listing->getAllImages() as $img) {
            if (str_starts_with($img, '/listing-images/')) {
                $filename = basename($img);
                $disk->delete('listing-images/'.$filename);
            }
        }

        $title = $listing->title;
        $listing->delete();

        return back()->with('status', "Listing '{$title}' has been removed from the marketplace.");
    }
}
