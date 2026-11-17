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
            'unit' => ['required', 'string', Rule::in(array_keys(Item::ALLOWED_UNITS))],
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

        $analysisToken = self::generateAnalysisToken(
            (int) Auth::id(),
            $validated['name'],
            (float) $validated['quantity']
        );
        session()->put('inventory_analysis_token', $analysisToken);

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'analysis_token' => $analysisToken,
            'saved_images' => $savedImages,
            'saved_image_slots' => $savedSlots,
            'item_data' => [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'quantity' => (float) $validated['quantity'],
                'unit' => $validated['unit'],
                'condition' => $validated['condition'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkApproved();
        $business = Auth::user();

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
            'unit' => ['required', 'string', Rule::in(array_keys(Item::ALLOWED_UNITS))],
            'reusability' => ['required', 'in:reusable,not_reusable'],
            'category' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'saved_images' => ['nullable', 'string'],
            'saved_image_slots' => ['nullable', 'string'],
            'image_1' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_2' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'image_3' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'listing_type' => ['nullable', 'in:buy,free'],
            'condition' => ['nullable', Rule::in(Item::ALLOWED_CONDITIONS)],
            'available_quantity' => ['nullable', 'numeric', 'min:0'],
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'redirect_to' => ['nullable', 'string'],
            'analysis_token' => ['nullable', 'string'],
            'publish_to_marketplace' => ['nullable', 'boolean'],
        ];

        if ($request->input('reusability') === 'reusable' && $isPublishingRequest) {
            $rules['listing_type'] = ['required', 'in:buy,free'];
            $rules['condition'] = ['required', Rule::in(Item::ALLOWED_CONDITIONS)];
            if ($request->input('listing_type') === 'buy') {
                $rules['price'] = ['required', 'numeric', 'min:0'];
            }
        } elseif ($request->input('reusability') === 'reusable' && $request->input('listing_type') === 'buy' && $request->filled('price')) {
            $rules['price'] = ['required', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules, [
            'unit.in' => 'Please select a valid Unit of Measurement from the dropdown.',
            'condition.in' => 'Please select a valid Material Condition (New, Damaged, or Used).',
            'category.required' => 'Please confirm or enter a valid Material Category.',
            'images.max' => 'You can upload a maximum of 3 photos.',
            'price.required' => 'Price per unit (RM) is required when listing type is Sell.',
        ]);

        // Enforce mandatory material analysis before publishing to Marketplace
        if ($isPublishingRequest && $validated['reusability'] === 'reusable') {
            $expectedToken = self::generateAnalysisToken(
                (int) $business->id,
                $validated['name'],
                (float) $validated['quantity']
            );
            $providedToken = (string) ($validated['analysis_token'] ?? '');

            if ($providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
                return back()->withErrors([
                    'analysis_token' => 'Material analysis is required before publishing to the Marketplace. Please analyze the material first.',
                ])->withInput();
            }
        }

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

        // Only process uploaded file inputs if pre-saved images from aiAnalyze were NOT already provided
        if (! $usedPreSaved) {
            $hasExplicitSlots = $request->hasFile('image_1') || $request->hasFile('image_2') || $request->hasFile('image_3');
            if ($hasExplicitSlots) {
                for ($slot = 1; $slot <= 3; $slot++) {
                    $file = $request->file("image_{$slot}");
                    if ($file && $file->isValid()) {
                        $imageSlots[$slot - 1] = $this->storeUploadedInventoryImage($file);
                    }
                }
            } elseif ($request->hasFile('images')) {
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
        }

        $uploadedImages = array_values(array_filter($imageSlots));

        if (empty($uploadedImages)) {
            return back()->withErrors(['images' => 'Please provide at least 1 photo for this material.'])->withInput();
        }

        $quantity = (float) $validated['quantity'];

        $reusability = $validated['reusability'];
        $category = trim((string) $validated['category']);
        $listingType = $validated['listing_type'] ?? 'buy';
        $condition = $validated['condition'] ?? 'Used';
        $price = ($listingType === 'free') ? 0 : (isset($validated['price']) && $validated['price'] !== '' ? (float) $validated['price'] : 0);
        $description = ! empty($validated['description']) ? $validated['description'] : "Material specifications for {$validated['name']}.";

        // Location resolution (use user entered or GPS or business profile location)
        $pickupLocation = trim((string) ($validated['pickup_location'] ?? ''));
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
        DB::transaction(function () use ($business, $validated, $description, $quantity, $category, $status, $imageSlots, $uploadedImages, $reusability, $listingType, $price, $condition, $locationCity, $locationState, $resolvedPickupAddress, $isPublishingRequest, &$createdItem): void {
            $createdItem = InventoryItem::create([
                'business_id' => $business->id,
                'inventory_item_id' => null,
                'name' => $validated['name'],
                'title' => $validated['name'],
                'description' => $description,
                'category' => $category,
                'quantity' => $quantity,
                'unit' => $validated['unit'],
                'reusability' => $reusability,
                'listing_type' => $listingType,
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

        // Only redirect to Helper if explicitly requested
        if ($reusability === 'not_reusable' && $request->input('redirect_to') === 'helper') {
            return redirect()->route('business.helper', ['item_id' => $createdItem->id])->with('status', "Non-reusable material '{$validated['name']}' saved in inventory and opened in EcoCycle Helper.");
        }

        if ($isPublishingRequest && $reusability === 'reusable' && $request->input('redirect_to') === 'marketplace') {
            return redirect()->route('business.marketplace')->with('status', "Material '{$validated['name']}' added to inventory and published to the EcoCycle Marketplace.");
        }

        return redirect()->route('business.inventory')->with('status', "Material '{$validated['name']}' has been added to your inventory.");
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', Rule::in(array_keys(Item::ALLOWED_UNITS))],
            'reusability' => ['nullable', 'in:reusable,not_reusable'],
            'listing_type' => ['nullable', 'in:buy,free'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'condition' => ['nullable', Rule::in(Item::ALLOWED_CONDITIONS)],
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

        $wasPublished = $item->isPublishedToMarketplace();
        $quantity = (float) $item->quantity;
        $status = $quantity <= 0 ? 'out_of_stock' : ($wasPublished && ($validated['reusability'] ?? $item->reusability) === 'reusable' ? 'active' : 'in_stock');

        $reusability = $validated['reusability'] ?? $item->reusability ?? 'reusable';
        $listingType = $validated['listing_type'] ?? $item->listing_type ?? 'buy';
        $condition = $validated['condition'] ?? $item->condition ?? 'Used';
        $price = ($listingType === 'free') ? 0 : (isset($validated['price']) && $validated['price'] !== '' ? (float) $validated['price'] : ($item->price ?? 0));

        $item->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $item->description,
            'category' => trim((string) $validated['category']),
            'unit' => $validated['unit'],
            'reusability' => $reusability,
            'listing_type' => $listingType,
            'price' => $price,
            'condition' => $condition,
            'image_path' => $uploadedImages[0] ?? null,
            'image_1' => $imageSlots[0],
            'image_2' => $imageSlots[1],
            'image_3' => $imageSlots[2],
            'images' => $uploadedImages,
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Clean up replaced/removed image files only if no longer referenced anywhere
        if (! empty($oldPathsToMaybeDelete)) {
            $disk = Storage::disk('public');
            foreach (array_unique($oldPathsToMaybeDelete) as $oldPath) {
                if (str_starts_with($oldPath, '/inventory-images/') && ! $this->isImageStillReferenced($oldPath)) {
                    $disk->delete('inventory-images/'.basename($oldPath));
                }
            }
        }

        // Only keep Marketplace synced if item was ALREADY published, or unpublish if switched to non-reusable
        if ($wasPublished || $reusability === 'not_reusable') {
            static::syncInventoryToMarketplace($item, $validated);
        }

        return back()->with('status', "Item '{$item->name}' updated successfully.");
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

        $wasPublished = $item->isPublishedToMarketplace();
        $newQty = $type === 'stock_in' ? ($currentQty + $adjQty) : ($currentQty - $adjQty);
        $change = $type === 'stock_in' ? $adjQty : -$adjQty;

        $status = $newQty <= 0
            ? ($wasPublished ? 'sold' : 'out_of_stock')
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
            if (! in_array($listingType, ['buy', 'free'], true)) {
                $listingType = 'buy';
            }
            $isFree = $listingType === 'free';
            $price = $isFree ? 0 : (isset($extraData['price']) && $extraData['price'] !== '' && $extraData['price'] !== null
                ? (float) $extraData['price']
                : ($item->price ? (float) $item->price : 0));

            $condition = $extraData['condition'] ?? $item->condition ?? 'Used';
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
