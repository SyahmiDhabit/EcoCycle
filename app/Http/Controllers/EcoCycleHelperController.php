<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\User;
use App\Services\EcoCycleHelperService;
use App\Services\RecyclingCentreLocatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EcoCycleHelperController extends Controller
{
    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    public function index(Request $request, EcoCycleHelperService $helper): View|RedirectResponse
    {
        /** @var User $business */
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return redirect()->route('business.pending');
        }

        // Fetch all business inventory items, with non-reusable items prioritized
        $items = Item::where('business_id', $business->id)
            ->latest()
            ->get();

        $selectedItem = null;
        $initialAnalysis = null;
        if ($itemId = $request->query('item_id')) {
            $selectedItem = $items->firstWhere('id', (int) $itemId);
            if ($selectedItem) {
                $initialAnalysis = $helper->analyze($selectedItem);
            }
        }

        if (! $selectedItem && $items->isNotEmpty()) {
            // Default to first non-reusable item if available, or first item
            $selectedItem = $items->firstWhere('reusability', 'not_reusable') ?: $items->first();
            if ($selectedItem) {
                $initialAnalysis = $helper->analyze($selectedItem);
            }
        }

        // Default coordinates based on business city/state
        $defaultCoords = RecyclingCentreLocatorService::getCityCoordinates($business->city ?: 'Shah Alam', $business->state ?: 'Selangor');

        // Nearby centres for selected item's category
        $materialCat = $selectedItem?->category ?? 'General';
        $nearbyCentres = RecyclingCentreLocatorService::findNearby($defaultCoords['lat'], $defaultCoords['lng'], $materialCat, 8);

        return view('business.helper.index', [
            'business' => $business,
            'items' => $items,
            'selectedItem' => $selectedItem,
            'initialAnalysis' => $initialAnalysis,
            'defaultCoords' => $defaultCoords,
            'nearbyCentres' => $nearbyCentres,
            'activeNav' => 'helper',
        ]);
    }

    public function chat(Request $request, EcoCycleHelperService $helper): JsonResponse
    {
        $this->checkApproved();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'item_id' => ['nullable', 'integer'],
            'history' => ['nullable', 'array'],
        ]);

        $contextItem = null;
        if (! empty($validated['item_id'])) {
            $item = Item::find($validated['item_id']);
            if ($item) {
                $contextItem = [
                    'id' => $item->id,
                    'name' => $item->name ?: $item->title,
                    'category' => $item->category,
                    'description' => $item->description,
                    'condition' => $item->condition,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'reusability' => $item->reusability,
                ];
            }
        }

        $result = $helper->reply($validated['message'], $contextItem, $validated['history'] ?? []);

        return response()->json([
            'success' => true,
            'reply' => $result['reply'],
            'suggested_questions' => $result['suggested_questions'],
        ]);
    }

    public function analyzeItem(Item $item, EcoCycleHelperService $helper): JsonResponse
    {
        $this->checkApproved();

        $analysis = $helper->analyze($item);

        /** @var User $business */
        $business = Auth::user();
        $coords = RecyclingCentreLocatorService::getCityCoordinates($business->city ?: 'Shah Alam', $business->state ?: 'Selangor');
        $nearby = RecyclingCentreLocatorService::findNearby($coords['lat'], $coords['lng'], $item->category, 6);

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name ?: $item->title,
                'category' => $item->category,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'condition' => $item->condition,
                'reusability' => $item->reusability,
                'image_path' => $item->getPrimaryImage(),
                'images' => $item->getAllImages(),
            ],
            'analysis' => $analysis,
            'nearby_centres' => $nearby,
        ]);
    }

    public function nearbyCentres(Request $request): JsonResponse
    {
        $this->checkApproved();

        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $centres = RecyclingCentreLocatorService::findNearby(
            (float) $validated['lat'],
            (float) $validated['lng'],
            $validated['category'] ?? null,
            12
        );

        return response()->json([
            'success' => true,
            'centres' => $centres,
        ]);
    }
}
