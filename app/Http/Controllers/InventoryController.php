<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\MarketplaceListing;
use App\Models\User;
use App\Services\EcoCycleHelperService;
use Illuminate\Http\JsonResponse;
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

    public function index(Request $request): View|RedirectResponse
    {
        /** @var User $business */
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return redirect()->route('business.pending');
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
            if ($status === 'in_stock') {
                $query->whereIn('status', ['in_stock', 'active'])->where('quantity', '>', 0);
            } elseif ($status === 'low_stock') {
                $query->where('status', 'low_stock');
            } elseif ($status === 'out_of_stock') {
                $query->where(function ($q) {
                    $q->whereIn('status', ['out_of_stock', 'sold'])->orWhere('quantity', '<=', 0);
                });
            } else {
                $query->where('status', $status);
            }
        }

        if ($reusability = $request->input('reusability')) {
            $query->where('reusability', $reusability);
        }

        $viewMode = $request->input('view', 'table');
        if (! in_array($viewMode, ['table', 'catalog', 'grid'])) {
            $viewMode = 'table';
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
            'viewMode' => $viewMode,
        ]);
    }

    /**
     * Gemini AI Material Analysis Endpoint
     * Analyzes uploaded material photos, name, and description.
     * Suggests: category, reusability (reusable / not_reusable), reason, and recommended actions.
     */
    public function aiAnalyze(Request $request, EcoCycleHelperService $helper): JsonResponse
    {
        $this->checkApproved();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'],
            'images' => ['required', 'array', 'min:1', 'max:3'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'images.required' => 'Please upload at least 1 photo for Gemini AI material analysis (minimum 1, maximum 3 photos).',
            'images.min' => 'Please upload at least 1 photo (minimum 1, maximum 3 photos).',
            'images.max' => 'You can upload a maximum of 3 photos.',
        ]);

        // Save up to 3 uploaded images into public storage
        $disk = Storage::disk('public');
        $savedImages = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                foreach (array_slice($files, 0, 3) as $file) {
                    if ($file && $file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'inv_'.Str::random(24).'.'.$ext;
                        $disk->putFileAs('inventory-images', $file, $filename);
                        $savedImages[] = '/inventory-images/'.$filename;
                    }
                }
            }
        }

        $analysis = $helper->classifyMaterial(
            $validated['name'],
            $validated['description'],
            (float) $validated['quantity'],
            $validated['unit'],
            $savedImages
        );

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'saved_images' => $savedImages,
            'item_data' => [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'quantity' => (float) $validated['quantity'],
                'unit' => $validated['unit'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkApproved();
        $business = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:30'],
            'reusability' => ['required', 'in:reusable,not_reusable'],
            'category' => ['nullable', 'string', 'max:60'],
            'sku' => ['nullable', 'string', 'max:100'],
            'minimum_threshold' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'saved_images' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'listing_type' => ['nullable', 'in:buy,borrow,free'],
            'condition' => ['nullable', 'string', 'max:40'],
            'available_quantity' => ['nullable', 'numeric', 'min:0'],
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'redirect_to' => ['nullable', 'string'],
        ];

        if ($request->input('reusability') === 'reusable' && $request->input('listing_type') === 'buy' && $request->filled('price')) {
            $rules['price'] = ['required', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules, [
            'images.max' => 'You can upload a maximum of 5 photos.',
            'price.required' => 'Price per unit (RM) is required when listing type is Sell.',
        ]);

        // Process images (either pre-saved from AI analysis or newly uploaded)
        $uploadedImages = [];
        if (! empty($validated['saved_images'])) {
            $decoded = json_decode($validated['saved_images'], true);
            if (is_array($decoded)) {
                $uploadedImages = array_slice($decoded, 0, 5);
            }
        }

        if ($request->hasFile('images')) {
            $disk = Storage::disk('public');
            $files = $request->file('images');
            if (is_array($files)) {
                foreach (array_slice($files, 0, max(0, 5 - count($uploadedImages))) as $file) {
                    if ($file && $file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'inv_'.Str::random(24).'.'.$ext;
                        $disk->putFileAs('inventory-images', $file, $filename);
                        $uploadedImages[] = '/inventory-images/'.$filename;
                    }
                }
            }
        }

        if (empty($uploadedImages)) {
            return back()->withErrors(['images' => 'Please provide at least 1 photo for this material.'])->withInput();
        }

        $quantity = (float) $validated['quantity'];
        $threshold = isset($validated['minimum_threshold']) && $validated['minimum_threshold'] !== null && $validated['minimum_threshold'] !== ''
            ? (float) $validated['minimum_threshold']
            : null;

        $reusability = $validated['reusability'];
        $category = ! empty($validated['category']) ? $validated['category'] : 'Other';
        $listingType = $validated['listing_type'] ?? 'buy';
        $condition = $validated['condition'] ?? ($reusability === 'reusable' ? 'Used - Good' : 'Scrap');
        $price = ($listingType === 'free') ? 0 : (isset($validated['price']) && $validated['price'] !== '' ? (float) $validated['price'] : 0);
        $description = ! empty($validated['description']) ? $validated['description'] : "Material specifications for {$validated['name']}.";

        // Location resolution (use user entered or GPS or business profile location)
        $pickupLocation = $validated['pickup_location'] ?? null;
        $locationCity = $business->city ?: 'Shah Alam';
        $locationState = $business->state ?: 'Selangor';
        if ($pickupLocation) {
            $parts = array_map('trim', explode(',', $pickupLocation));
            if (count($parts) >= 2) {
                $locationCity = $parts[0];
                $locationState = end($parts);
            }
        }

        $status = 'in_stock';
        if ($quantity <= 0) {
            $status = 'out_of_stock';
        } elseif ($reusability === 'reusable') {
            $status = 'active';
        } elseif ($threshold !== null && $quantity <= $threshold) {
            $status = 'low_stock';
        }

        $createdItem = null;
        DB::transaction(function () use ($business, $validated, $description, $quantity, $category, $threshold, $status, $uploadedImages, $reusability, $listingType, $price, $condition, $locationCity, $locationState, &$createdItem): void {
            $createdItem = InventoryItem::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'title' => $validated['name'],
                'description' => $description,
                'sku' => $validated['sku'] ?? ('INV-' . strtoupper(Str::random(6))),
                'category' => $category,
                'quantity' => $quantity,
                'unit' => $validated['unit'],
                'reusability' => $reusability,
                'listing_type' => $listingType,
                'price' => $price,
                'condition' => $condition,
                'location_city' => $locationCity,
                'location_state' => $locationState,
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
                    'inventory_item_id' => $createdItem->id,
                    'transaction_type' => 'stock_in',
                    'quantity_change' => $quantity,
                    'quantity_after' => $quantity,
                    'notes' => 'Initial stock on hand entry',
                ]);
            }

            if ($reusability === 'reusable') {
                // Synchronize with Marketplace & Business Profile Directory
                static::syncInventoryToMarketplace($createdItem, $validated);

                BusinessNotification::send(
                    $business->id,
                    'Material Added & Live on Marketplace',
                    "Added {$quantity} {$validated['unit']} of '{$validated['name']}' ({$category}). It is now live on the EcoCycle Marketplace for other businesses to browse.",
                    'inventory_added',
                    '/business/marketplace',
                    'green'
                );
            } else {
                BusinessNotification::send(
                    $business->id,
                    'Non-Reusable Material Logged',
                    "Added {$quantity} {$validated['unit']} of '{$validated['name']}' ({$category}) as internal scrap. Forwarded to EcoCycle Helper for recovery.",
                    'inventory_added',
                    "/business/helper?item_id={$createdItem->id}",
                    'blue'
                );
            }
        });

        // If Non-Reusable: Forward item information directly to EcoCycle Helper
        if ($reusability === 'not_reusable') {
            return redirect()->route('business.helper', ['item_id' => $createdItem->id])->with('status', "Non-reusable material '{$validated['name']}' saved and forwarded to EcoCycle Helper for circular recommendations!");
        }

        // If Reusable: If redirect_to marketplace requested, redirect to marketplace, otherwise inventory
        if ($request->input('redirect_to') === 'marketplace') {
            return redirect()->route('business.marketplace')->with('status', "Material '{$validated['name']}' added to inventory and automatically published to the EcoCycle Marketplace!");
        }

        return redirect()->route('business.inventory')->with('status', "Material '{$validated['name']}' added to inventory and automatically published to the EcoCycle Marketplace!");
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
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $uploadedImages = $item->getAllImages();
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $disk = Storage::disk('public');
                $newImages = [];
                foreach (array_slice($files, 0, 3) as $file) {
                    if ($file && $file->isValid()) {
                        $ext = $file->getClientOriginalExtension() ?: 'jpg';
                        $filename = 'inv_'.Str::random(24).'.'.$ext;
                        $disk->putFileAs('inventory-images', $file, $filename);
                        $newImages[] = '/inventory-images/'.$filename;
                    }
                }
                if (! empty($newImages)) {
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
                $disk->delete('inventory-images/'.basename($img));
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
        $business = $item->business ?: User::find($item->business_id);

        if (($item->reusability ?? 'reusable') === 'reusable') {
            $listingType = $extraData['listing_type'] ?? $item->listing_type ?? 'buy';
            if (! in_array($listingType, ['buy', 'borrow', 'free'], true)) {
                $listingType = 'buy';
            }
            $isFree = $listingType === 'free';
            $price = $isFree ? 0 : (isset($extraData['price']) && $extraData['price'] !== '' && $extraData['price'] !== null
                ? (float) $extraData['price']
                : ($item->price ? (float) $item->price : 0));

            $condition = $extraData['condition'] ?? $item->condition ?? 'Used - Good';
            $qty = (float) $item->quantity;
            $status = $qty > 0 ? 'active' : 'sold';

            $item->update([
                'title' => $item->name,
                'name' => $item->name,
                'listing_type' => $listingType,
                'reusability' => 'reusable',
                'price' => $price,
                'is_free' => $isFree,
                'condition' => $condition,
                'location_city' => $item->location_city ?: ($business?->city ?: 'Kuala Lumpur'),
                'location_state' => $item->location_state ?: ($business?->state ?: 'Selangor'),
                'status' => $status,
                'inventory_item_id' => $item->id,
            ]);

            return MarketplaceListing::find($item->id);
        }

        // If marked as not_reusable, dissociate inventory_item_id so it is excluded from marketplace
        $item->update([
            'inventory_item_id' => null,
            'status' => ((float) $item->quantity > 0) ? 'in_stock' : 'out_of_stock',
        ]);

        return null;
    }
}
