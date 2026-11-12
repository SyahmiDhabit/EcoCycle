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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InventoryController extends Controller
{
    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    public function index(Request $request): View
    {
        /** @var \App\Models\User $business */
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return view('business.verification', ['business' => $business]);
        }

        // Auto-heal / synchronize any reusable inventory items missing a marketplace listing
        $unsynced = $business->inventoryItems()
            ->where('reusability', 'reusable')
            ->whereDoesntHave('marketplaceListing')
            ->get();

        foreach ($unsynced as $itemToSync) {
            static::syncInventoryToMarketplace($itemToSync);
        }

        $query = $business->inventoryItems()->latest();

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($reusability = $request->input('reusability')) {
            $query->where('reusability', $reusability);
        }

        $items = $query->paginate(15)->withQueryString();
        $categories = $business->inventoryItems()->distinct()->pluck('category')->filter()->values();

        return view('business.inventory.index', [
            'business' => $business,
            'items' => $items,
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'selectedReusability' => $reusability,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkApproved();
        $business = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:60'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:20'],
            'reusability' => ['nullable', 'in:reusable,not_reusable'],
            'listing_type' => ['nullable', 'in:buy,borrow,free'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string', 'max:40'],
            'minimum_threshold' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'images.required' => 'Please upload at least 1 photo for this inventory item (maximum 5).',
            'images.min' => 'Please upload at least 1 photo for this inventory item (minimum 1, maximum 5).',
            'images.max' => 'You can upload a maximum of 5 photos.',
        ]);

        // Process up to 5 uploaded images
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $disk = Storage::disk('public');
                foreach (array_slice($files, 0, 5) as $file) {
                    if ($file && $file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'inv_' . Str::random(24) . '.' . $ext;
                        $disk->putFileAs('inventory-images', $file, $filename);
                        $uploadedImages[] = '/inventory-images/' . $filename;
                    }
                }
            }
        }

        $quantity = (float) $validated['quantity'];
        $threshold = isset($validated['minimum_threshold']) && $validated['minimum_threshold'] !== null && $validated['minimum_threshold'] !== ''
            ? (float) $validated['minimum_threshold']
            : null;

        $status = 'in_stock';
        if ($quantity <= 0) {
            $status = 'out_of_stock';
        } elseif ($threshold !== null && $quantity <= $threshold) {
            $status = 'low_stock';
        }

        $reusability = $validated['reusability'] ?? 'reusable';
        $listingType = $validated['listing_type'] ?? 'buy';
        $condition = $validated['condition'] ?? 'Used - Good';
        $price = ($listingType === 'free') ? 0 : (isset($validated['price']) && $validated['price'] !== '' ? (float) $validated['price'] : 0);

        DB::transaction(function () use ($business, $validated, $quantity, $threshold, $status, $uploadedImages, $reusability, $listingType, $price, $condition): void {
            $item = InventoryItem::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'category' => $validated['category'],
                'quantity' => $quantity,
                'unit' => $validated['unit'],
                'reusability' => $reusability,
                'listing_type' => $listingType,
                'price' => $price,
                'condition' => $condition,
                'image_path' => $uploadedImages[0] ?? null,
                'image_1' => $uploadedImages[0] ?? null,
                'image_2' => $uploadedImages[1] ?? null,
                'image_3' => $uploadedImages[2] ?? null,
                'image_4' => $uploadedImages[3] ?? null,
                'image_5' => $uploadedImages[4] ?? null,
                'images' => $uploadedImages,
                'minimum_threshold' => $threshold,
                'status' => $status,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($quantity > 0) {
                InventoryTransaction::create([
                    'business_id' => $business->id,
                    'inventory_item_id' => $item->id,
                    'transaction_type' => 'stock_in',
                    'quantity_change' => $quantity,
                    'quantity_after' => $quantity,
                    'notes' => 'Initial stock on hand entry',
                ]);
            }

            // Synchronize with Marketplace & Business Profile Directory
            static::syncInventoryToMarketplace($item, $validated);

            if ($reusability === 'reusable') {
                BusinessNotification::send(
                    $business->id,
                    'Material Added & Live on Marketplace',
                    "Added {$quantity} {$validated['unit']} of '{$validated['name']}' ({$validated['category']}). It is automatically active on the Marketplace and visible in your business directory profile.",
                    'inventory_added',
                    '/business/marketplace',
                    'green'
                );
            } else {
                BusinessNotification::send(
                    $business->id,
                    'Scrap Material Logged in Inventory',
                    "Added {$quantity} {$validated['unit']} of '{$validated['name']}' ({$validated['category']}) as internal scrap. AI Scrap Advisor guidance is ready.",
                    'inventory_added',
                    '/business/inventory',
                    'blue'
                );
            }
        });

        $successMsg = ($reusability === 'reusable')
            ? "Inventory item '{$validated['name']}' added and automatically published to Marketplace & Directory!"
            : "Scrap item '{$validated['name']}' saved to internal inventory. Click 'AI Advisor' for circular recovery suggestions.";

        return back()->with('status', $successMsg);
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:60'],
            'unit' => ['required', 'string', 'max:20'],
            'reusability' => ['nullable', 'in:reusable,not_reusable'],
            'listing_type' => ['nullable', 'in:buy,borrow,free'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['nullable', 'string', 'max:40'],
            'minimum_threshold' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $uploadedImages = $item->getAllImages();
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $disk = Storage::disk('public');
                $newImages = [];
                foreach (array_slice($files, 0, 5) as $file) {
                    if ($file && $file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'inv_' . Str::random(24) . '.' . $ext;
                        $disk->putFileAs('inventory-images', $file, $filename);
                        $newImages[] = '/inventory-images/' . $filename;
                    }
                }
                if (!empty($newImages)) {
                    $uploadedImages = $newImages;
                }
            }
        }

        $threshold = isset($validated['minimum_threshold']) && $validated['minimum_threshold'] !== null && $validated['minimum_threshold'] !== ''
            ? (float) $validated['minimum_threshold']
            : null;

        $quantity = (float) $item->quantity;
        $status = 'in_stock';
        if ($quantity <= 0) {
            $status = 'out_of_stock';
        } elseif ($threshold !== null && $quantity <= $threshold) {
            $status = 'low_stock';
        }

        $reusability = $validated['reusability'] ?? $item->reusability ?? 'reusable';
        $listingType = $validated['listing_type'] ?? $item->listing_type ?? 'buy';
        $condition = $validated['condition'] ?? $item->condition ?? 'Used - Good';
        $price = ($listingType === 'free') ? 0 : (isset($validated['price']) && $validated['price'] !== '' ? (float) $validated['price'] : ($item->price ?? 0));

        $item->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $item->description,
            'sku' => $validated['sku'] ?? null,
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'reusability' => $reusability,
            'listing_type' => $listingType,
            'price' => $price,
            'condition' => $condition,
            'image_path' => $uploadedImages[0] ?? null,
            'image_1' => $uploadedImages[0] ?? null,
            'image_2' => $uploadedImages[1] ?? null,
            'image_3' => $uploadedImages[2] ?? null,
            'image_4' => $uploadedImages[3] ?? null,
            'image_5' => $uploadedImages[4] ?? null,
            'images' => $uploadedImages,
            'minimum_threshold' => $threshold,
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Sync with marketplace
        static::syncInventoryToMarketplace($item, $validated);

        return back()->with('status', "Item '{$item->name}' updated and synced successfully.");
    }

    public function adjust(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);

        $validated = $request->validate([
            'type' => ['required', 'in:stock_in,stock_out'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $currentQty = (float) $item->quantity;
        $adjQty = (float) $validated['quantity'];
        $type = $validated['type'];

        if ($type === 'stock_out' && $adjQty > $currentQty) {
            return back()->withErrors([
                'quantity' => "Cannot deduct {$adjQty} {$item->unit}. Only {$currentQty} {$item->unit} currently available in stock.",
            ])->withInput();
        }

        $newQty = $type === 'stock_in' ? ($currentQty + $adjQty) : ($currentQty - $adjQty);
        $change = $type === 'stock_in' ? $adjQty : -$adjQty;

        $threshold = $item->minimum_threshold !== null ? (float) $item->minimum_threshold : null;
        $status = 'in_stock';
        if ($newQty <= 0) {
            $status = 'out_of_stock';
        } elseif ($threshold !== null && $newQty <= $threshold) {
            $status = 'low_stock';
        }

        DB::transaction(function () use ($item, $newQty, $status, $type, $change, $validated): void {
            $item->update([
                'quantity' => $newQty,
                'status' => $status,
            ]);

            InventoryTransaction::create([
                'business_id' => $item->business_id,
                'inventory_item_id' => $item->id,
                'transaction_type' => $type,
                'quantity_change' => $change,
                'quantity_after' => $newQty,
                'notes' => $validated['notes'] ?? ($type === 'stock_in' ? 'Stock replenished' : 'Stock dispatched / used'),
            ]);

            // Sync with marketplace
            static::syncInventoryToMarketplace($item);
        });

        $actionWord = $type === 'stock_in' ? 'added to' : 'deducted from';
        return back()->with('status', "{$adjQty} {$item->unit} {$actionWord} '{$item->name}'. New balance: {$newQty} {$item->unit} (Marketplace synced).");
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);

        $disk = Storage::disk('public');
        foreach ($item->getAllImages() as $img) {
            if (str_starts_with($img, '/inventory-images/')) {
                $disk->delete('inventory-images/' . basename($img));
            }
        }

        // Delete any linked marketplace listing
        MarketplaceListing::where('inventory_item_id', $item->id)->delete();

        $name = $item->name;
        $item->delete();

        return back()->with('status', "Inventory item '{$name}' and its marketplace listing deleted.");
    }

    /**
     * Synchronize an inventory item with the public marketplace listings.
     * - Reusable materials automatically become active marketplace listings (and appear on directory profile).
     * - Non-reusable scrap materials remain private internal inventory and are removed from the marketplace.
     */
    public static function syncInventoryToMarketplace(InventoryItem $item, array $extraData = []): ?MarketplaceListing
    {
        if (($item->reusability ?? 'reusable') === 'reusable') {
            $business = $item->business ?: User::find($item->business_id);
            $listingType = $extraData['listing_type'] ?? $item->listing_type ?? 'buy';
            if (!in_array($listingType, ['buy', 'borrow', 'free'], true)) {
                $listingType = 'buy';
            }
            $isFree = $listingType === 'free';
            $price = $isFree ? 0 : (isset($extraData['price']) && $extraData['price'] !== '' && $extraData['price'] !== null
                ? (float) $extraData['price']
                : ($item->price ? (float) $item->price : 0));

            $condition = $extraData['condition'] ?? $item->condition ?? 'Used - Good';
            $qty = (float) $item->quantity;
            $status = $qty > 0 ? 'active' : 'sold';

            $listing = MarketplaceListing::where('inventory_item_id', $item->id)->first();
            if (! $listing) {
                $listing = new MarketplaceListing();
                $listing->business_id = $item->business_id;
                $listing->inventory_item_id = $item->id;
            }

            $listing->title = $item->name;
            $listing->description = !empty($item->description)
                ? $item->description
                : (!empty($listing->description) ? $listing->description : 'Reusable surplus material available for circular exchange on EcoCycle.');
            $listing->category = $item->category;
            $listing->quantity = $qty;
            $listing->unit = $item->unit;
            $listing->listing_type = $listingType;
            $listing->reusability = 'reusable';
            $listing->price = $price;
            $listing->is_free = $isFree;
            $listing->condition = $condition;
            $listing->location_city = $listing->location_city ?: ($business?->city ?: 'Kuala Lumpur');
            $listing->location_state = $listing->location_state ?: ($business?->state ?: 'Selangor');

            $images = $item->getAllImages();
            $listing->image_path = $images[0] ?? null;
            $listing->image_1 = $images[0] ?? null;
            $listing->image_2 = $images[1] ?? null;
            $listing->image_3 = $images[2] ?? null;
            $listing->image_4 = $images[3] ?? null;
            $listing->image_5 = $images[4] ?? null;
            $listing->images = $images;
            $listing->status = $status;
            $listing->save();

            return $listing;
        }

        // If marked as not_reusable, delete any active marketplace listing for this item
        MarketplaceListing::where('inventory_item_id', $item->id)->delete();
        return null;
    }
}
