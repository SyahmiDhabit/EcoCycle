<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
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

        DB::transaction(function () use ($business, $validated, $quantity, $threshold, $status, $uploadedImages): void {
            $item = InventoryItem::create([
                'business_id' => $business->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'category' => $validated['category'],
                'quantity' => $quantity,
                'unit' => $validated['unit'],
                'reusability' => $validated['reusability'] ?? 'reusable',
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

            BusinessNotification::send(
                $business->id,
                'Inventory Stock Logged',
                "Added {$quantity} {$validated['unit']} of '{$validated['name']}' ({$validated['category']}) to your internal inventory.",
                'inventory_added',
                '/business/inventory',
                'blue'
            );
        });

        return back()->with('status', "Inventory item '{$validated['name']}' added successfully.");
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

        $item->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $item->description,
            'sku' => $validated['sku'] ?? null,
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'reusability' => $validated['reusability'] ?? $item->reusability ?? 'reusable',
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
        });

        $actionWord = $type === 'stock_in' ? 'added to' : 'deducted from';
        return back()->with('status', "{$adjQty} {$item->unit} {$actionWord} '{$item->name}'. New balance: {$newQty} {$item->unit}.");
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

        $name = $item->name;
        $item->delete();

        return back()->with('status', "Inventory item '{$name}' deleted.");
    }
}
