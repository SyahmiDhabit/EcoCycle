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

        // EcoCycle Helper is STRICTLY for Non-Reusable items only (never display Reusable items)
        $items = Item::where('business_id', $business->id)
            ->where('reusability', 'not_reusable')
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

        // Default coordinates based on business city/state
        $defaultCoords = RecyclingCentreLocatorService::getCityCoordinates($business->city ?: 'Shah Alam', $business->state ?: 'Selangor');

        // Nearby verified centres for selected non-reusable item's category (or all verified if no item selected yet)
        $materialCat = $selectedItem?->category;
        $nearbyCentres = RecyclingCentreLocatorService::findNearby($defaultCoords['lat'], $defaultCoords['lng'], $materialCat, 10, true);

        return view('business.helper.index', [
            'business' => $business,
            'items' => $items,
            'selectedItem' => $selectedItem,
            'initialAnalysis' => $initialAnalysis,
            'defaultCoords' => $defaultCoords,
            'nearbyCentres' => $nearbyCentres,
            'verifiedMatchFound' => ! empty($nearbyCentres),
            'googleMapsApiKey' => trim((string) config('services.google_maps.api_key', '')),
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
            $item = Item::where('id', (int) $validated['item_id'])
                ->where('reusability', 'not_reusable')
                ->first();
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

        if (($item->reusability ?? 'reusable') !== 'not_reusable') {
            return response()->json([
                'success' => false,
                'message' => 'EcoCycle Helper is only available for Non-Reusable items. Reusable items should be managed in Inventory or published to the Marketplace.',
            ], 422);
        }

        $analysis = $helper->analyze($item);

        /** @var User $business */
        $business = Auth::user();
        $coords = RecyclingCentreLocatorService::getCityCoordinates($business->city ?: 'Shah Alam', $business->state ?: 'Selangor');
        $nearby = RecyclingCentreLocatorService::findNearby($coords['lat'], $coords['lng'], $item->category, 8, true);

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
            'verified_match_found' => ! empty($nearby),
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

        $category = $validated['category'] ?? null;
        $centres = RecyclingCentreLocatorService::findNearby(
            (float) $validated['lat'],
            (float) $validated['lng'],
            $category,
            12,
            true
        );

        $alternativeRecommendations = empty($centres) ? [
            'Contact your local council (PBT) or state solid waste authority (SWCorp / KDEB Waste Management) to verify approved collection points for this material.',
            'Consult licensed Malaysian waste collectors registered under the Department of Environment (DOE Malaysia) before transporting specialized or contaminated materials.',
            'Keep the material segregated, dry, and clearly labelled at your premises until an authorized facility confirms acceptance.',
        ] : [];

        return response()->json([
            'success' => true,
            'centres' => $centres,
            'verified_match_found' => ! empty($centres),
            'category' => $category,
            'alternative_recommendations' => $alternativeRecommendations,
        ]);
    }
}
