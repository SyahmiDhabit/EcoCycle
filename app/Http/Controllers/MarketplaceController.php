<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
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

    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $business */
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return redirect()->route('business.pending');
        }

        // Auto-heal / synchronize any reusable inventory items missing from marketplace_listings
        $unsynced = InventoryItem::where('reusability', 'reusable')
            ->whereDoesntHave('marketplaceListing')
            ->get();
        foreach ($unsynced as $itemToSync) {
            InventoryController::syncInventoryToMarketplace($itemToSync);
        }

        // Query marketplace listings directly from database
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
            ->whereIn('status', ['active', 'in_stock', 'low_stock'])
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

        if ($listingType !== '') {
            if ($listingType === 'free') {
                $query->where(function ($q) {
                    $q->where('listing_type', 'free')->orWhere('is_free', true);
                });
            } else {
                $query->where('listing_type', $listingType);
            }
        }

        if ($reusability !== '' && $tab === 'my_listings') {
            $query->where('reusability', $reusability);
        }

        $listings = $query->latest()->get();

        // Counts for tabs
        $reusableCount = MarketplaceListing::query()
            ->whereIn('status', ['active', 'in_stock', 'low_stock'])
            ->where('quantity', '>', 0)
            ->where('reusability', 'reusable')
            ->count();

        $nonReusableCount = MarketplaceListing::query()
            ->whereIn('status', ['active', 'in_stock', 'low_stock'])
            ->where('quantity', '>', 0)
            ->where('reusability', 'not_reusable')
            ->count();

        $myListingsCount = $business->marketplaceListings()
            ->whereIn('status', ['active', 'in_stock', 'low_stock'])
            ->where('quantity', '>', 0)
            ->count();

        // Get distinct categories and locations for the current category context
        $catLocationScope = MarketplaceListing::query()
            ->whereIn('status', ['active', 'in_stock', 'low_stock'])
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

        // User's inventory items for easy listing
        $myInventoryItems = $business->inventoryItems()
            ->where('status', '!=', 'out_of_stock')
            ->where('reusability', 'reusable')
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
            'currentTab' => $tab,
            'reusableCount' => $reusableCount,
            'nonReusableCount' => $nonReusableCount,
            'myListingsCount' => $myListingsCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $validated = $request->validate([
            'inventory_item_id' => ['nullable', 'exists:item,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:60'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:20'],
            'listing_type' => ['required', 'in:buy,borrow,free'],
            'reusability' => ['required', 'in:reusable'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string', 'max:40'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'reusability.in' => 'Only reusable materials can be listed on the marketplace. Non-reusable items remain saved in your internal inventory only.',
        ]);

        $reusability = $validated['reusability'] ?? 'reusable';
        $listingType = $validated['listing_type'];
        $isFree = $listingType === 'free';
        $price = $isFree ? 0 : (isset($validated['price']) ? (float) $validated['price'] : null);
        $description = ! empty($validated['description']) ? $validated['description'] : 'Surplus material available for circular exchange on EcoCycle.';
        $condition = ! empty($validated['condition']) ? $validated['condition'] : 'Used - Good';

        // Handle 1 to 5 uploaded images
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $disk = Storage::disk('public');
                foreach (array_slice($files, 0, 5) as $file) {
                    if ($file && $file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'listing_'.Str::random(24).'.'.$ext;
                        $disk->putFileAs('listing-images', $file, $filename);
                        $uploadedImages[] = '/listing-images/'.$filename;
                    }
                }
            }
        }

        // If inventory item is specified, ensure it belongs to the user and is reusable
        $inventoryItemId = $validated['inventory_item_id'] ?? null;
        if ($inventoryItemId) {
            $listing = MarketplaceListing::find($inventoryItemId);
            abort_unless($listing && $listing->business_id === $business->id, 403);
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
                'unit' => $validated['unit'],
                'listing_type' => $listingType,
                'reusability' => $reusability,
                'price' => $price,
                'is_free' => $isFree,
                'condition' => $condition,
                'status' => 'active',
                'inventory_item_id' => $listing->id,
                'image_path' => $uploadedImages[0] ?? $listing->image_path,
                'image_1' => $uploadedImages[0] ?? $listing->image_1,
                'image_2' => $uploadedImages[1] ?? $listing->image_2,
                'image_3' => $uploadedImages[2] ?? $listing->image_3,
                'image_4' => $uploadedImages[3] ?? $listing->image_4,
                'image_5' => $uploadedImages[4] ?? $listing->image_5,
                'images' => ! empty($uploadedImages) ? $uploadedImages : $listing->images,
            ]);
        } else {
            $listing = MarketplaceListing::create([
                'business_id' => $business->id,
                'title' => $validated['title'],
                'name' => $validated['title'],
                'description' => $description,
                'category' => $validated['category'],
                'quantity' => (float) $validated['quantity'],
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
                'image_4' => $uploadedImages[3] ?? null,
                'image_5' => $uploadedImages[4] ?? null,
                'images' => $uploadedImages,
                'location_city' => $business->city ?: 'Kuala Lumpur',
                'location_state' => $business->state ?: 'Selangor',
                'status' => 'active',
            ]);

            $listing->inventory_item_id = $listing->id;
            $listing->save();

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
                $disk->delete('listing-images/'.$filename);
            }
        }

        $title = $listing->title;
        $listing->delete();

        return back()->with('status', "Listing '{$title}' has been removed from the marketplace.");
    }
}
