<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Item;
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
use Illuminate\Validation\Rule;
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

        $query = $business->inventoryItems()->latest();

        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            if ($status === 'in_stock') {
                $query->whereIn('status', ['in_stock', 'active'])->where('quantity', '>', 0);
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

    public static function generateAnalysisToken(int $businessId, string $name, float $quantity): string
    {
        $payload = $businessId.'|'.trim($name).'|'.number_format($quantity, 2, '.', '');

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    /**
     * Check whether an inventory image path is still referenced by another item row.
     */
    private function isImageStillReferenced(string $path, ?int $excludeItemId = null): bool
    {
        $query = InventoryItem::query()->where(function ($q) use ($path): void {
            $q->where('image_path', $path)
                ->orWhere('image_1', $path)
                ->orWhere('image_2', $path)
                ->orWhere('image_3', $path);
        });

        if ($excludeItemId !== null) {
            $query->where('id', '!=', $excludeItemId);
        }

        return $query->exists();
    }

    /**
     * Store a single uploaded inventory image on public storage and return its URL path.
     */
    private function storeUploadedInventoryImage(\Illuminate\Http\UploadedFile $file): string
    {
        $disk = Storage::disk('public');
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = 'inv_'.Str::random(24).'.'.$ext;
        $disk->putFileAs('inventory-images', $file, $filename);

        return '/inventory-images/'.$filename;
    }

    /**
     * Material Analysis Endpoint
     * Analyzes uploaded material photos, name, and description.
     * Suggests: dynamic category, reusability (reusable / not_reusable), reason, and recommended actions.
     */
    public function aiAnalyze(Request $request, EcoCycleHelperService $helper): JsonResponse
    {
        $this->checkApproved();

        $hasExplicitSlots = $request->hasFile('image_1') || $request->hasFile('image_2') || $request->hasFile('image_3');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', Rule::in(Item::allowedUnitKeys())],
            'condition' => ['required', 'string', Rule::in(Item::ALLOWED_CONDITIONS)],
            'image_1' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_2' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_3' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];

        if ($hasExplicitSlots) {
            $rules['images'] = ['nullable', 'array', 'max:3'];
            $rules['images.*'] = ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'];
        } else {
            $rules['images'] = ['required', 'array', 'min:1', 'max:3'];
            $rules['images.*'] = ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'];
        }

        $validated = $request->validate($rules, [
            'unit.in' => 'Please select a valid Unit of Measurement from the dropdown.',
            'condition.in' => 'Please select a valid Material Condition (New, Damaged, or Used).',
            'images.required' => 'Please upload at least 1 photo for material analysis (minimum 1, maximum 3 photos).',
            'images.min' => 'Please upload at least 1 photo (minimum 1, maximum 3 photos).',
            'images.max' => 'You can upload a maximum of 3 photos.',
        ]);

        // Collect actual UploadedFile objects per slot (up to 3) so Gemini analyzes the real image files
        $slotFiles = [null, null, null];
        if ($hasExplicitSlots) {
            for ($slot = 1; $slot <= 3; $slot++) {
                $file = $request->file("image_{$slot}");
                if ($file && $file->isValid()) {
                    $slotFiles[$slot - 1] = $file;
                }
            }
        } elseif ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $slotIdx = 0;
                foreach (array_slice($files, 0, 3) as $file) {
                    if ($file && $file->isValid() && $slotIdx < 3) {
                        $slotFiles[$slotIdx] = $file;
                        $slotIdx++;
                    }
                }
            }
        }

        $actualFiles = array_values(array_filter($slotFiles));
        if (empty($actualFiles)) {
            return response()->json([
                'success' => false,
                'message' => 'Uploaded material photos could not be read. Please upload 1 to 3 valid images and retry.',
            ], 422);
        }

        try {
            $analysis = $helper->classifyMaterial(
                $validated['name'],
                $validated['description'],
                (float) $validated['quantity'],
                $validated['unit'],
                $actualFiles,
                $validated['condition']
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // Only persist uploaded images once AI visual analysis succeeds (each image saved once per slot)
        $savedSlots = [null, null, null];
        for ($i = 0; $i < 3; $i++) {
            if ($slotFiles[$i] !== null) {
                $savedSlots[$i] = $this->storeUploadedInventoryImage($slotFiles[$i]);
            }
        }
        $savedImages = array_values(array_filter($savedSlots));

        /** @var User $business */
        $business = Auth::user();
        $city = $business->city ?: 'Shah Alam';
        $state = $business->state ?: 'Selangor';
        $cityCoords = \App\Services\RecyclingCentreLocatorService::getCityCoordinates($city, $state);
        $lat = (float) ($request->input('lat') ?: $cityCoords['lat']);
        $lng = (float) ($request->input('lng') ?: $cityCoords['lng']);

        $categoryForLocator = $analysis['suggested_category'] ?? $request->input('category') ?? $validated['name'];
        $nearbyCentres = \App\Services\RecyclingCentreLocatorService::findNearby($lat, $lng, $categoryForLocator, 4, false);
        if (empty($nearbyCentres)) {
            $nearbyCentres = \App\Services\RecyclingCentreLocatorService::findNearby($lat, $lng, null, 3, false);
        }

        $analysis['nearby_centres'] = $nearbyCentres;
        $analysis['disposal_locations'] = $nearbyCentres;
        $analysis['strategy'] = $analysis['strategy'] ?? $analysis['disposal_method'] ?? 'Segregation and certified industrial recovery';

        $analysisToken = self::generateAnalysisToken(
            (int) Auth::id(),
            $validated['name'],
            (float) $validated['quantity']
        );
        session()->put('inventory_analysis_token', $analysisToken);

        // Persist analyzed draft in session so Update Inventory Details is the central confirmation step before saving
        session()->put('inventory_pending_draft', [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'quantity' => (float) $validated['quantity'],
            'unit' => $validated['unit'],
            'condition' => $validated['condition'],
            'saved_images' => $savedImages,
            'saved_image_slots' => $savedSlots,
            'analysis' => $analysis,
            'analysis_token' => $analysisToken,
            'created_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'disposal_locations' => $nearbyCentres,
            'nearby_centres' => $nearbyCentres,
            'analysis_token' => $analysisToken,
            'saved_images' => $savedImages,
            'saved_image_slots' => $savedSlots,
            'update_details_url' => route('business.inventory.prepare'),
            'item_data' => [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'quantity' => (float) $validated['quantity'],
                'unit' => $validated['unit'],
                'condition' => $validated['condition'],
            ],
        ]);
    }

    /**
     * Main central workflow step after Material Analysis:
     * Review, complete, edit, and confirm item details on Update Inventory Details BEFORE saving to `item`.
     */
    public function prepare(): View|RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $draft = session('inventory_pending_draft');
        if (! is_array($draft) || empty($draft['name'])) {
            return redirect()->route('business.inventory', ['add' => 1])
                ->with('status', 'Please upload material photos and submit item details for Material Analysis first.');
        }

        $categories = $business->inventoryItems()->distinct()->pluck('category')->filter()->values();

        return view('business.inventory.prepare', [
            'business' => $business,
            'draft' => $draft,
            'categories' => $categories,
        ]);
    }

    /**
     * Cancel an unsaved draft from the Update Inventory Details page and clean up any unreferenced draft photos.
     */
    public function cancelDraft(): RedirectResponse
    {
        $this->checkApproved();

        $draft = session()->pull('inventory_pending_draft');
        if (is_array($draft)) {
            $draftImages = array_unique(array_filter(array_merge(
                (array) ($draft['saved_images'] ?? []),
                (array) ($draft['saved_image_slots'] ?? [])
            ), fn ($v): bool => is_string($v) && trim($v) !== ''));

            $disk = Storage::disk('public');
            foreach ($draftImages as $imgPath) {
                if (str_starts_with($imgPath, '/inventory-images/') && ! $this->isImageStillReferenced($imgPath)) {
                    $disk->delete('inventory-images/'.basename($imgPath));
                }
            }
        }

        return redirect()->route('business.inventory')
            ->with('status', 'Item submission cancelled. No item was saved to your inventory.');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkApproved();
        $business = Auth::user();

        $isFromPrepare = $request->boolean('from_prepare');
        $isPublishingRequest = $request->boolean('publish_to_marketplace') || $request->input('redirect_to') === 'marketplace';

        // Enforce Non-Reusable publishing restriction strictly on backend
        if ($request->input('reusability') === 'not_reusable' && $isPublishingRequest) {
            return back()->withErrors([
                'reusability' => 'Non-reusable materials cannot be published to the Marketplace.',
            ])->withInput();
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', Rule::in(Item::allowedUnitKeys())],
            'reusability' => ['required', 'in:reusable,not_reusable'],
            'category' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'saved_images' => ['nullable', 'string'],
            'saved_image_slots' => ['nullable', 'string'],
            'image_1' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_2' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_3' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_image_1' => ['nullable', 'boolean'],
            'remove_image_2' => ['nullable', 'boolean'],
            'remove_image_3' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'listing_type' => ['nullable', 'in:buy,free,lend'],
            'condition' => ['nullable', Rule::in(Item::ALLOWED_CONDITIONS)],
            'available_quantity' => ['nullable', 'numeric', 'min:0'],
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'ai_analysis_json' => ['nullable', 'string'],
            'redirect_to' => ['nullable', 'string'],
            'analysis_token' => ['nullable', 'string'],
            'publish_to_marketplace' => ['nullable', 'boolean'],
            'from_prepare' => ['nullable', 'boolean'],
        ];

        if ($request->input('reusability') === 'reusable' && ($isPublishingRequest || $isFromPrepare)) {
            $rules['listing_type'] = ['required', 'in:buy,free,lend'];
            $rules['condition'] = ['required', Rule::in(Item::ALLOWED_CONDITIONS)];
            if ($request->input('listing_type') === 'buy') {
                $rules['price'] = ['required', 'numeric', 'gt:0'];
            }
        } elseif ($request->filled('price') && $request->input('listing_type') === 'buy') {
            $rules['price'] = ['numeric', 'gt:0'];
        }

        $validated = $request->validate($rules, [
            'unit.in' => 'Please select a valid Unit of Measurement from the dropdown.',
            'condition.in' => 'Please select a valid Material Condition (New, Damaged, or Used).',
            'category.required' => 'Please confirm or enter a valid Material Category.',
            'images.max' => 'You can upload a maximum of 3 photos.',
            'price.required' => 'Price per Unit (RM) is required when Listing Type is Sell.',
            'price.gt' => 'Sell listings must have a valid Price per Unit (RM) greater than RM 0.00.',
        ]);

        // Resolve 3 independent image slots without ever duplicating pre-saved AI analysis images
        $imageSlots = [null, null, null];
        $usedPreSaved = false;

        if (! empty($validated['saved_image_slots'])) {
            $decodedSlots = json_decode($validated['saved_image_slots'], true);
            if (is_array($decodedSlots)) {
                for ($i = 0; $i < 3; $i++) {
                    $val = $decodedSlots[$i] ?? null;
                    if (is_string($val) && trim($val) !== '') {
                        $imageSlots[$i] = trim($val);
                        $usedPreSaved = true;
                    }
                }
            }
        }

        if (! $usedPreSaved && ! empty($validated['saved_images'])) {
            $decoded = json_decode($validated['saved_images'], true);
            if (is_array($decoded)) {
                $uniqueSaved = array_values(array_unique(array_filter(
                    $decoded,
                    fn ($v): bool => is_string($v) && trim($v) !== ''
                )));
                for ($i = 0; $i < min(3, count($uniqueSaved)); $i++) {
                    $imageSlots[$i] = trim($uniqueSaved[$i]);
                    $usedPreSaved = true;
                }
            }
        }

        // Allow replacing/removing individual slots on top of pre-saved analysis slots (e.g. from Update Inventory Details)
        $hasExplicitSlots = $request->hasFile('image_1') || $request->hasFile('image_2') || $request->hasFile('image_3')
            || $request->boolean('remove_image_1') || $request->boolean('remove_image_2') || $request->boolean('remove_image_3');

        if ($hasExplicitSlots) {
            for ($slot = 1; $slot <= 3; $slot++) {
                $idx = $slot - 1;
                $file = $request->file("image_{$slot}");
                if ($file && $file->isValid()) {
                    $imageSlots[$idx] = $this->storeUploadedInventoryImage($file);
                } elseif ($request->boolean("remove_image_{$slot}")) {
                    $imageSlots[$idx] = null;
                }
            }
        } elseif (! $usedPreSaved && $request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $slotIdx = 0;
                foreach (array_slice($files, 0, 3) as $file) {
                    if ($file && $file->isValid() && $slotIdx < 3) {
                        $imageSlots[$slotIdx] = $this->storeUploadedInventoryImage($file);
                        $slotIdx++;
                    }
                }
            }
        }

        $uploadedImages = array_values(array_filter($imageSlots));

        if (empty($uploadedImages)) {
            return back()->withErrors(['images' => 'Please provide at least 1 photo for this material.'])->withInput();
        }

        $quantity = (float) $validated['quantity'];

        $reusability = $validated['reusability'];
        $category = trim((string) $validated['category']);
        $listingType = $validated['listing_type'] ?? 'buy';
        if (! in_array($listingType, Item::ALLOWED_LISTING_TYPES, true)) {
            $listingType = 'buy';
        }
        $isFree = in_array($listingType, ['free', 'lend'], true);
        $condition = $validated['condition'] ?? 'Used';
        // Price is optional during initial inventory creation (null until configured for Marketplace Sell; 0.00 for Free/Lend)
        $price = $isFree
            ? 0.00
            : (isset($validated['price']) && $validated['price'] !== '' ? (float) $validated['price'] : null);
        $description = ! empty($validated['description']) ? $validated['description'] : "Material specifications for {$validated['name']}.";

        $aiAnalysis = null;
        if (! empty($validated['ai_analysis_json'])) {
            $decodedAnalysis = json_decode((string) $validated['ai_analysis_json'], true);
            if (is_array($decodedAnalysis)) {
                $aiAnalysis = $decodedAnalysis;
            }
        }

        // Location resolution (use pickup_address, pickup_location, or business profile location)
        $pickupLocation = trim((string) ($validated['pickup_address'] ?? $validated['pickup_location'] ?? ''));
        $locationCity = $business->city ?: 'Shah Alam';
        $locationState = $business->state ?: 'Selangor';
        if ($pickupLocation !== '') {
            $parts = array_map('trim', explode(',', $pickupLocation));
            if (count($parts) >= 2) {
                $locationCity = $parts[0];
                $locationState = end($parts);
            }
        }
        $resolvedPickupAddress = $pickupLocation !== ''
            ? $pickupLocation
            : trim(implode(', ', array_filter([
                $business->registered_address,
                trim(implode(' ', array_filter([$business->postcode, $locationCity]))),
                $locationState,
            ])));

        // Save to Inventory only by default (do NOT auto-publish to Marketplace)
        $status = 'in_stock';
        if ($quantity <= 0) {
            $status = 'out_of_stock';
        } elseif ($isPublishingRequest && $reusability === 'reusable') {
            $status = 'active';
        }

        $createdItem = null;
        DB::transaction(function () use ($business, $validated, $description, $quantity, $category, $status, $imageSlots, $uploadedImages, $reusability, $listingType, $isFree, $price, $condition, $locationCity, $locationState, $resolvedPickupAddress, $aiAnalysis, $isPublishingRequest, &$createdItem): void {
            $createdItem = InventoryItem::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'title' => $validated['name'],
                'description' => $description,
                'category' => $category,
                'quantity' => $quantity,
                'original_quantity' => $quantity,
                'unit' => $validated['unit'],
                'reusability' => $reusability,
                'listing_type' => $listingType,
                'is_free' => $isFree,
                'price' => $price,
                'condition' => $condition,
                'location_city' => $locationCity,
                'location_state' => $locationState,
                'pickup_address' => $resolvedPickupAddress !== '' ? $resolvedPickupAddress : null,
                'image_path' => $uploadedImages[0] ?? null,
                'image_1' => $imageSlots[0],
                'image_2' => $imageSlots[1],
                'image_3' => $imageSlots[2],
                'images' => $uploadedImages,
                'ai_analysis' => $aiAnalysis,
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

            if ($isPublishingRequest && $reusability === 'reusable') {
                static::syncInventoryToMarketplace($createdItem, $validated);

                BusinessNotification::send(
                    $business->id,
                    'Material Added & Published to Marketplace',
                    "Added {$quantity} {$validated['unit']} of '{$validated['name']}' ({$category}) and published it to the EcoCycle Marketplace.",
                    'inventory_added',
                    '/business/marketplace',
                    'green'
                );
            } elseif ($reusability === 'reusable') {
                BusinessNotification::send(
                    $business->id,
                    'Reusable Material Added to Inventory',
                    "Added {$quantity} {$validated['unit']} of '{$validated['name']}' ({$category}) to your inventory. You can publish it to the Marketplace anytime from Inventory.",
                    'inventory_added',
                    '/business/inventory',
                    'green'
                );
            } else {
                BusinessNotification::send(
                    $business->id,
                    'Non-Reusable Material Logged in Inventory',
                    "Added {$quantity} {$validated['unit']} of '{$validated['name']}' ({$category}) to your inventory. Open EcoCycle Helper for recycling and disposal guidance.",
                    'inventory_added',
                    "/business/helper?item_id={$createdItem->id}",
                    'blue'
                );
            }
        });

        // Clear pending draft from session once confirmed and saved into `item`
        session()->forget('inventory_pending_draft');

        // Main Add Inventory workflow:
        // 1. Reusable: added directly to inventory and proceeds to the listing process (edit page to configure Sell/Free/Lend, price, pickup address & publish)
        // 2. Non-Reusable: saved in inventory and opens in EcoCycle Helper for full disposal and recycling guidance
        if ($isFromPrepare && $createdItem) {
            if ($reusability === 'not_reusable') {
                return redirect()->route('business.helper', ['item_id' => $createdItem->id])
                    ->with('status', "Non-reusable material '{$validated['name']}' has been saved to Inventory Items and forwarded to EcoCycle Helper.");
            }

            return redirect()->route('business.inventory.edit', $createdItem)
                ->with('status', "Material '{$validated['name']}' has been saved to Inventory Items. Review the saved details below and click Publish to Marketplace when ready.");
        }

        if ($reusability === 'reusable') {
            if ($isPublishingRequest && $request->input('redirect_to') === 'marketplace') {
                return redirect()->route('business.marketplace')->with('status', "Material '{$validated['name']}' added to inventory and published to the EcoCycle Marketplace.");
            }

            if ($request->input('redirect_to') === 'listing' || $request->input('redirect_to') === 'edit') {
                return redirect()->route('business.inventory.edit', $createdItem)
                    ->with('status', "Reusable material '{$validated['name']}' has been added to your inventory. Complete your listing details below to publish to the Marketplace.");
            }

            return redirect()->route('business.inventory')->with('status', "Material '{$validated['name']}' has been added to your inventory.");
        }

        if ($reusability === 'not_reusable') {
            if ($request->input('redirect_to') === 'inventory') {
                return redirect()->route('business.inventory')->with('status', "Non-reusable material '{$validated['name']}' has been saved to your inventory.");
            }

            return redirect()->route('business.helper', ['item_id' => $createdItem->id])
                ->with('status', "Non-reusable material '{$validated['name']}' saved in inventory. Step-by-step disposal and recycling guidance is available below.");
        }

        return redirect()->route('business.inventory')->with('status', "Material '{$validated['name']}' has been added to your inventory.");
    }

    /**
     * Show dedicated Edit Inventory Details page (Step 4: update details, quantity, Sell/Free, Price (RM), Pickup Address).
     */
    public function edit(InventoryItem $item): View
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);

        /** @var User $business */
        $business = Auth::user();
        $categories = $business->inventoryItems()->distinct()->pluck('category')->filter()->values();

        return view('business.inventory.edit', [
            'business' => $business,
            'item' => $item,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);

        // Enforce permanent Sold Out rule: sold-out non-lend surplus submissions cannot be restocked or reactivated
        if ($item->isPermanentlySoldOut() && $request->filled('quantity') && (float) $request->input('quantity') > 0) {
            return back()->withErrors([
                'quantity' => 'This item is permanently Sold Out and cannot be restocked or reactivated. Please add a new inventory item submission for new surplus stock.',
            ])->withInput();
        }

        $wasPublished = $item->isPublishedToMarketplace();
        $targetReusability = (string) ($request->input('reusability') ?: ($item->reusability ?? 'reusable'));
        $targetListingType = (string) ($request->input('listing_type') ?: ($item->listing_type ?? 'buy'));

        $priceRules = ['nullable', 'numeric', 'min:0'];
        if ($targetReusability === 'reusable' && $targetListingType === 'buy') {
            if ($wasPublished || $request->filled('price') || $request->boolean('require_sell_price')) {
                $priceRules = ['required', 'numeric', 'gt:0'];
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'string', Rule::in(Item::allowedUnitKeys())],
            'reusability' => ['nullable', 'in:reusable,not_reusable'],
            'listing_type' => ['nullable', 'in:buy,free,lend'],
            'price' => $priceRules,
            'condition' => ['nullable', Rule::in(Item::ALLOWED_CONDITIONS)],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'image_1' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_2' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_3' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_image_1' => ['nullable', 'boolean'],
            'remove_image_2' => ['nullable', 'boolean'],
            'remove_image_3' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'unit.in' => 'Please select a valid Unit of Measurement from the dropdown.',
            'condition.in' => 'Please select a valid Material Condition (New, Damaged, or Used).',
            'price.required' => 'Please enter a valid Price (RM) greater than RM 0.00 when Listing Type is Sell.',
            'price.gt' => 'Price (RM) must be greater than RM 0.00 when Listing Type is Sell.',
            'images.max' => 'You can upload a maximum of 3 photos.',
        ]);

        $imageSlots = $item->getImageSlots();
        $oldPathsToMaybeDelete = [];

        $hasExplicitSlotOps = $request->hasFile('image_1') || $request->hasFile('image_2') || $request->hasFile('image_3')
            || $request->boolean('remove_image_1') || $request->boolean('remove_image_2') || $request->boolean('remove_image_3');

        if ($hasExplicitSlotOps) {
            for ($slot = 1; $slot <= 3; $slot++) {
                $idx = $slot - 1;
                $file = $request->file("image_{$slot}");
                if ($file && $file->isValid()) {
                    if (! empty($imageSlots[$idx])) {
                        $oldPathsToMaybeDelete[] = $imageSlots[$idx];
                    }
                    $imageSlots[$idx] = $this->storeUploadedInventoryImage($file);
                } elseif ($request->boolean("remove_image_{$slot}")) {
                    if (! empty($imageSlots[$idx])) {
                        $oldPathsToMaybeDelete[] = $imageSlots[$idx];
                    }
                    $imageSlots[$idx] = null;
                }
            }
        } elseif ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $newSlots = [null, null, null];
                $slotIdx = 0;
                foreach (array_slice($files, 0, 3) as $file) {
                    if ($file && $file->isValid() && $slotIdx < 3) {
                        $newSlots[$slotIdx] = $this->storeUploadedInventoryImage($file);
                        $slotIdx++;
                    }
                }
                if (! empty(array_filter($newSlots))) {
                    foreach ($imageSlots as $oldPath) {
                        if (! empty($oldPath)) {
                            $oldPathsToMaybeDelete[] = $oldPath;
                        }
                    }
                    $imageSlots = $newSlots;
                }
            }
        }

        $uploadedImages = array_values(array_filter($imageSlots));

        $oldQty = (float) $item->quantity;
        $quantity = array_key_exists('quantity', $validated) && $validated['quantity'] !== null
            ? (float) $validated['quantity']
            : $oldQty;

        if ($item->isPermanentlySoldOut()) {
            $quantity = 0.0;
        }

        $reusability = $validated['reusability'] ?? $item->reusability ?? 'reusable';
        $listingType = $validated['listing_type'] ?? $item->listing_type ?? 'buy';
        if (! in_array($listingType, Item::ALLOWED_LISTING_TYPES, true)) {
            $listingType = 'buy';
        }
        $condition = $validated['condition'] ?? $item->condition ?? 'Used';
        $pickupAddress = array_key_exists('pickup_address', $validated)
            ? (trim((string) $validated['pickup_address']) ?: null)
            : $item->pickup_address;

        if ($reusability === 'not_reusable') {
            $isFree = false;
            $price = null;
            $status = $quantity <= 0 ? 'out_of_stock' : 'in_stock';
        } elseif ($item->isPermanentlySoldOut()) {
            $isFree = in_array($listingType, ['free', 'lend'], true);
            $price = $isFree ? 0.00 : ($item->price !== null ? (float) $item->price : null);
            $status = 'sold';
        } elseif (in_array($listingType, ['free', 'lend'], true)) {
            $isFree = true;
            $price = 0.00;
            $status = $quantity <= 0
                ? ($wasPublished ? 'sold' : 'out_of_stock')
                : ($wasPublished ? 'active' : 'in_stock');
        } else {
            $isFree = false;
            $price = (array_key_exists('price', $validated) && $validated['price'] !== null && $validated['price'] !== '')
                ? (float) $validated['price']
                : ($wasPublished ? (float) $item->price : ($request->has('price') ? null : $item->price));
            $status = $quantity <= 0
                ? ($wasPublished ? 'sold' : 'out_of_stock')
                : ($wasPublished ? 'active' : 'in_stock');
        }

        DB::transaction(function () use ($item, $validated, $quantity, $oldQty, $reusability, $listingType, $isFree, $price, $condition, $pickupAddress, $uploadedImages, $imageSlots, $status, $wasPublished): void {
            $item->update([
                'name' => $validated['name'],
                'title' => $validated['name'],
                'description' => $validated['description'] ?? $item->description,
                'category' => trim((string) $validated['category']),
                'quantity' => $quantity,
                'unit' => $validated['unit'],
                'reusability' => $reusability,
                'listing_type' => $listingType,
                'is_free' => $isFree,
                'price' => $price,
                'condition' => $condition,
                'pickup_address' => $pickupAddress,
                'image_path' => $uploadedImages[0] ?? null,
                'image_1' => $imageSlots[0],
                'image_2' => $imageSlots[1],
                'image_3' => $imageSlots[2],
                'images' => $uploadedImages,
                'status' => $status,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (abs($quantity - $oldQty) > 0.0001) {
                $delta = $quantity - $oldQty;
                InventoryTransaction::create([
                    'business_id' => $item->business_id,
                    'inventory_item_id' => $item->id,
                    'transaction_type' => $delta > 0 ? 'stock_in' : 'stock_out',
                    'quantity_change' => $delta,
                    'quantity_after' => $quantity,
                    'notes' => 'Quantity updated via Edit Inventory Details',
                ]);
            }

            // Only keep Marketplace synced if item was ALREADY published, or unpublish if switched to non-reusable
            if ($wasPublished || $reusability === 'not_reusable') {
                static::syncInventoryToMarketplace($item, $validated);
            }
        });

        // Clean up replaced/removed image files only if no longer referenced anywhere
        if (! empty($oldPathsToMaybeDelete)) {
            $disk = Storage::disk('public');
            foreach (array_unique($oldPathsToMaybeDelete) as $oldPath) {
                if (str_starts_with($oldPath, '/inventory-images/') && ! $this->isImageStillReferenced($oldPath)) {
                    $disk->delete('inventory-images/'.basename($oldPath));
                }
            }
        }

        if ($request->input('redirect_to') === 'edit') {
            return redirect()->route('business.inventory.edit', $item)
                ->with('status', "Changes saved for '{$item->name}'.");
        }

        return back()->with('status', "Item '{$item->name}' updated successfully.");
    }

    /**
     * Separate explicit action: Publish a confirmed Reusable inventory item to the Marketplace.
     */
    public function publish(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);

        if ($item->isPermanentlySoldOut()) {
            return back()->withErrors([
                'quantity' => 'This item is permanently Sold Out and cannot be reactivated or republished. Please add a new inventory item submission for new surplus stock.',
            ]);
        }

        if (($item->reusability ?? 'not_reusable') !== 'reusable') {
            return back()->withErrors([
                'reusability' => 'Only confirmed Reusable items can be published to the Marketplace. Non-Reusable items remain in Inventory.',
            ]);
        }

        if ((float) $item->quantity <= 0) {
            return back()->withErrors([
                'quantity' => 'Available quantity must be greater than 0 before publishing to the Marketplace.',
            ]);
        }

        $listingType = (string) ($request->input('listing_type') ?: ($item->listing_type ?: 'buy'));
        $rawPrice = $request->has('price') ? $request->input('price') : $item->price;
        $pickupAddress = $request->has('pickup_address')
            ? trim((string) $request->input('pickup_address'))
            : trim((string) ($item->pickup_address ?? ''));

        if (! in_array($listingType, Item::ALLOWED_LISTING_TYPES, true)) {
            return back()->withErrors([
                'listing_type' => 'Only Sell, Free, and Lend listing types are supported on the Marketplace.',
            ]);
        }

        $isFree = in_array($listingType, ['free', 'lend'], true);
        if ($isFree) {
            $price = 0.00;
        } else {
            if ($rawPrice === null || $rawPrice === '' || ! is_numeric($rawPrice) || (float) $rawPrice <= 0) {
                return back()->withErrors([
                    'price' => 'Sell items must have a valid Price per Unit (RM) greater than RM 0.00 before publishing. Please update the price in Update Item Details.',
                ]);
            }
            $price = round((float) $rawPrice, 2);
        }

        $item->update([
            'title' => $item->name,
            'listing_type' => $listingType,
            'is_free' => $isFree,
            'price' => $price,
            'pickup_address' => $pickupAddress !== '' ? $pickupAddress : $item->pickup_address,
            'status' => 'active',
        ]);

        $typeLabel = match ($listingType) {
            'free' => 'for Free (RM 0.00)',
            'lend' => 'for Lending (RM 0.00)',
            default => 'for Sale (RM '.number_format($price, 2).' per '.$item->unit.')',
        };

        BusinessNotification::send(
            $item->business_id,
            'Material Published to Marketplace',
            "'{$item->name}' is now published on the EcoCycle Marketplace {$typeLabel}.",
            'marketplace_listing',
            '/business/marketplace',
            'green'
        );

        return redirect()->route('business.marketplace')
            ->with('status', "Material '{$item->name}' published to the Marketplace {$typeLabel}.");
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

        if ($type === 'stock_in' && $item->isPermanentlySoldOut()) {
            return back()->withErrors([
                'quantity' => 'This item is permanently Sold Out and cannot be restocked. Please add a new inventory item submission for new surplus stock.',
            ])->withInput();
        }

        if ($type === 'stock_out' && $adjQty > $currentQty) {
            return back()->withErrors([
                'quantity' => "Cannot deduct {$adjQty} {$item->unit}. Only {$currentQty} {$item->unit} currently available in stock.",
            ])->withInput();
        }

        $wasPublished = $item->isPublishedToMarketplace();
        $newQty = $type === 'stock_in' ? ($currentQty + $adjQty) : ($currentQty - $adjQty);
        $change = $type === 'stock_in' ? $adjQty : -$adjQty;

        $status = $newQty <= 0
            ? 'out_of_stock'
            : ($wasPublished ? 'active' : 'in_stock');

        DB::transaction(function () use ($item, $newQty, $status, $type, $change, $validated, $wasPublished): void {
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

            // Only sync Marketplace status if this item is already published to Marketplace
            if ($wasPublished) {
                static::syncInventoryToMarketplace($item);
            }
        });

        $actionWord = $type === 'stock_in' ? 'added to' : 'deducted from';
        $syncSuffix = $wasPublished ? ' (Marketplace synced)' : '';

        return back()->with('status', "{$adjQty} {$item->unit} {$actionWord} '{$item->name}'. New balance: {$newQty} {$item->unit}{$syncSuffix}.");
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);

        $disk = Storage::disk('public');
        foreach ($item->getAllImages() as $img) {
            if (str_starts_with($img, '/inventory-images/') && ! $this->isImageStillReferenced($img, $item->id)) {
                $disk->delete('inventory-images/'.basename($img));
            }
        }

        $name = $item->name;
        $item->delete();

        return back()->with('status', "Inventory item '{$name}' deleted.");
    }

    /**
     * Synchronize an already-published inventory item with its Marketplace state,
     * or unpublish it if switched to Non-Reusable.
     */
    public static function syncInventoryToMarketplace(InventoryItem $item, array $extraData = []): ?MarketplaceListing
    {
        $business = $item->business ?: User::find($item->business_id);

        if (($item->reusability ?? 'reusable') === 'reusable') {
            $listingType = $extraData['listing_type'] ?? $item->listing_type ?? 'buy';
            if (! in_array($listingType, Item::ALLOWED_LISTING_TYPES, true)) {
                $listingType = 'buy';
            }
            $isFree = in_array($listingType, ['free', 'lend'], true);
            $price = $isFree ? 0.00 : (isset($extraData['price']) && $extraData['price'] !== '' && $extraData['price'] !== null
                ? (float) $extraData['price']
                : ($item->price !== null ? (float) $item->price : null));

            $condition = $extraData['condition'] ?? $item->condition ?? 'Used';
            $qty = (float) $item->quantity;
            $status = $qty > 0 ? 'active' : ($item->isPermanentlySoldOut() ? 'sold' : 'out_of_stock');

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
            ]);

            return MarketplaceListing::find($item->id);
        }

        // Non-reusable items remain private internal inventory (never active on Marketplace)
        $item->update([
            'price' => null,
            'is_free' => false,
            'status' => ((float) $item->quantity > 0) ? 'in_stock' : 'out_of_stock',
        ]);

        return null;
    }
}
