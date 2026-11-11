<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryItem;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    public function index(Request $request): View
    {
        /** @var User $business */
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return view('business.verification', ['business' => $business]);
        }

        // Query marketplace listings directly from database
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $location = trim((string) $request->query('location', ''));
        $listingType = trim((string) $request->query('listing_type', ''));
        $reusability = trim((string) $request->query('reusability', ''));
        $myListingsOnly = $request->boolean('my_listings');

        $query = MarketplaceListing::query()
            ->with('business')
            ->where('status', 'active');

        if ($myListingsOnly) {
            $query->where('business_id', $business->id);
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

        if ($listingType !== '') {
            if ($listingType === 'free') {
                $query->where(function ($q) {
                    $q->where('listing_type', 'free')->orWhere('is_free', true);
                });
            } else {
                $query->where('listing_type', $listingType);
            }
        }

        if ($reusability !== '') {
            $query->where('reusability', $reusability);
        }

        $listings = $query->latest()->get();

        // Get distinct categories and locations
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

        // User's inventory items for easy listing
        $myInventoryItems = $business->inventoryItems()
            ->where('status', '!=', 'out_of_stock')
            ->orderBy('name')
            ->get();

        return view('business.marketplace.index', [
            'business' => $business,
            'listings' => $listings,
            'categories' => $categories,
            'locations' => $locations,
            'myInventoryItems' => $myInventoryItems,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedLocation' => $location,
            'selectedListingType' => $listingType,
            'selectedReusability' => $reusability,
            'myListingsOnly' => $myListingsOnly,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $validated = $request->validate([
            'inventory_item_id' => ['nullable', 'exists:inventory_items,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:60'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:20'],
            'listing_type' => ['required', 'in:buy,borrow,free'],
            'reusability' => ['required', 'in:reusable,not_reusable'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string', 'max:40'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $listingType = $validated['listing_type'];
        $isFree = $listingType === 'free';
        $price = $isFree ? 0 : (isset($validated['price']) ? (float) $validated['price'] : null);
        $description = !empty($validated['description']) ? $validated['description'] : 'Surplus material available for circular exchange on EcoCycle.';
        $condition = !empty($validated['condition']) ? $validated['condition'] : 'Used - Good';

        // Handle 1 to 5 uploaded images
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $disk = Storage::disk('public');
                foreach (array_slice($files, 0, 5) as $file) {
                    if ($file && $file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'listing_' . Str::random(24) . '.' . $ext;
                        $disk->putFileAs('listing-images', $file, $filename);
                        $uploadedImages[] = '/listing-images/' . $filename;
                    }
                }
            }
        }

        // If inventory item is specified, ensure it belongs to the user
        $inventoryItemId = $validated['inventory_item_id'] ?? null;
        if ($inventoryItemId) {
            $item = InventoryItem::find($inventoryItemId);
            abort_unless($item && $item->business_id === $business->id, 403);
        }

        $listing = MarketplaceListing::create([
            'business_id' => $business->id,
            'inventory_item_id' => $inventoryItemId,
            'title' => $validated['title'],
            'description' => $description,
            'category' => $validated['category'],
            'quantity' => (float) $validated['quantity'],
            'unit' => $validated['unit'],
            'listing_type' => $listingType,
            'reusability' => $validated['reusability'],
            'price' => $price,
            'is_free' => $isFree,
            'condition' => $condition,
            'image_path' => $uploadedImages[0] ?? null,
            'images' => $uploadedImages,
            'location_city' => $business->city ?: 'Kuala Lumpur',
            'location_state' => $business->state ?: 'Selangor',
            'status' => 'active',
        ]);

        $typeLabel = match ($listingType) {
            'borrow' => 'for Borrowing',
            'free' => 'for Free Claim',
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
                $disk->delete('listing-images/' . $filename);
            }
        }

        $title = $listing->title;
        $listing->delete();

        return back()->with('status', "Listing '{$title}' has been removed from the marketplace.");
    }
}
