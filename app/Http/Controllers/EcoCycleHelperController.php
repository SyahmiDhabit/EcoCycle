<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\User;
use App\Models\WasteRecord;
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
        } elseif ($items->isNotEmpty()) {
            $selectedItem = $items->first();
        }

        if ($selectedItem) {
            // Carry existing AI analysis results generated during Add New Inventory directly
            $initialAnalysis = $selectedItem->ai_analysis;
            if (empty($initialAnalysis)) {
                $initialAnalysis = $helper->analyze($selectedItem);
            }
        }

        // Use registered SME company's address as the main reference point for GPS and location searches
        $businessAddress = trim(implode(', ', array_filter([
            $business->registered_address,
            $business->postcode,
            $business->city,
            $business->state,
        ])));
        if ($businessAddress === '') {
            $businessAddress = 'Registered SME Premises';
        }

        $businessCoords = RecyclingCentreLocatorService::getBusinessCoordinates($business);

        $nearbyCentres = $this->closestFacilities($businessCoords, $selectedItem, $initialAnalysis);

        $draft = $request->session()->get('waste_report_draft');
        $hasWasteRecord = $selectedItem !== null && $this->hasWasteRecord($selectedItem);
        $wasteReportFacilities = ! $hasWasteRecord && isset($draft['fields']) && $selectedItem?->id === $draft['item_id']
            ? $this->closestFacilities($businessCoords, $selectedItem, null)
            : [];

        return view('business.helper.index', [
            'business' => $business,
            'businessAddress' => $businessAddress,
            'businessCoords' => $businessCoords,
            'defaultCoords' => $businessCoords,
            'items' => $items,
            'selectedItem' => $selectedItem,
            'initialAnalysis' => $initialAnalysis,
            'nearbyCentres' => $nearbyCentres,
            'hasWasteRecord' => $hasWasteRecord,
            'wasteReportFacilities' => $wasteReportFacilities,
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
                ->where('business_id', $request->user()->id)
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

        // Carry forward existing AI analysis results from Add New Inventory; fallback to helper only if missing
        $analysis = $item->ai_analysis;
        if (empty($analysis)) {
            $analysis = $helper->analyze($item);
        }

        /** @var User $business */
        $business = Auth::user();
        $coords = RecyclingCentreLocatorService::getBusinessCoordinates($business);
        $nearby = $this->closestFacilities($coords, $item, $analysis);

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
                'has_waste_record' => $this->hasWasteRecord($item),
            ],
            'analysis' => $analysis,
            'nearby_centres' => $nearby,
            'verified_match_found' => ! empty($nearby),
        ]);
    }

    public function reanalyzeItem(Item $item, EcoCycleHelperService $helper): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);
        abort_unless($item->reusability === 'not_reusable', 422);

        $redirect = redirect()->route('business.helper', ['item_id' => $item->id]);

        try {
            $analysis = $helper->classifyMaterial(
                $item->name ?: $item->title,
                (string) $item->description,
                (float) $item->quantity,
                $item->unit,
                $item->getAllImages(),
                $item->condition ?? 'Used'
            );
        } catch (\RuntimeException $error) {
            return $redirect->withErrors(['analysis' => $error->getMessage()]);
        }

        if (($analysis['ai_source'] ?? null) !== 'Google Gemini AI') {
            return $redirect->withErrors(['analysis' => 'Gemini photo analysis is unavailable. The existing guidance is still saved.']);
        }

        $analysis['strategy'] = $analysis['disposal_method'];
        $analysis['repurpose_ideas'] = $analysis['repurposing_ideas'];
        $item->update(['ai_analysis' => $analysis]);

        return $redirect->with('status', 'Photo analysis completed. The updated guidance is saved.');
    }

    public function suggestFromWasteRecords(Request $request, Item $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);
        abort_unless($item->reusability === 'not_reusable', 422);

        if ($this->hasWasteRecord($item)) {
            return redirect()->route('business.helper', ['item_id' => $item->id])
                ->withErrors(['waste_report' => 'This item already has a record in Waste Monitoring.']);
        }

        $request->session()->put('waste_report_draft', [
            'item_id' => $item->id,
            'fields' => [
                'Material' => $item->name,
                'Category' => mb_substr((string) $item->category, 0, 60),
                'Quantity' => $item->quantity.' '.$item->unit,
                'Recorded date' => now()->format('d M Y'),
                'Status' => WasteRecord::statusLabel('in_storage'),
                'Recorded as diverted' => 'No',
                'Current destination' => 'Not recorded',
                'Current destination address' => 'Not recorded',
                'Facility payment' => 'Not recorded',
                'Notes' => 'None recorded',
            ],
            'text' => 'Confirm which facility accepts this material before scheduling collection.',
        ]);

        return redirect()->route('business.helper', ['item_id' => $item->id]);
    }

    public function saveWasteReport(Request $request, Item $item): RedirectResponse
    {
        $this->checkApproved();
        abort_unless($item->business_id === Auth::id(), 403);
        abort_unless($item->reusability === 'not_reusable' && $item->quantity > 0, 422);

        $draft = $request->session()->get('waste_report_draft');
        if (($draft['item_id'] ?? null) !== $item->id || !isset($draft['fields'])) {
            return back()->withErrors(['waste_report' => 'Generate a report before saving.']);
        }

        $validated = $request->validate(['facility_id' => ['required', 'integer']]);
        $coordinates = RecyclingCentreLocatorService::getBusinessCoordinates($request->user());
        $facilities = $this->closestFacilities($coordinates, $item, null);
        $facility = collect($facilities)->firstWhere('id', (int) $validated['facility_id']);
        if ($facility === null) {
            return back()->withErrors(['waste_report' => 'Choose a facility from the report’s list.']);
        }

        $item->getConnection()->transaction(function () use ($item, $facility): void {
            $lockedItem = Item::query()->lockForUpdate()->findOrFail($item->id);
            abort_unless($lockedItem->business_id === $item->business_id && $lockedItem->reusability === 'not_reusable' && $lockedItem->quantity > 0, 422);

            if ($this->hasWasteRecord($lockedItem)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'waste_report' => 'This item already has a record in Waste Monitoring.',
                ]);
            }

            $record = new WasteRecord([
                'business_id' => $lockedItem->business_id,
                'inventory_item_id' => $lockedItem->id,
                'material_name' => $lockedItem->name,
                'waste_type' => mb_substr($lockedItem->category, 0, 60),
                'quantity' => $lockedItem->quantity,
                'unit' => $lockedItem->unit,
                'status' => 'in_storage',
                'is_diverted' => false,
                'recorded_at' => now()->toDateString(),
            ]);

            $record->fill([
                'facility_name' => $facility['name'],
                'facility_location' => $facility['address'],
            ])->save();
        });

        $request->session()->forget('waste_report_draft');

        return redirect()->route('business.waste.index')->with('status', 'Waste report and planned destination saved.');
    }

    private function hasWasteRecord(Item $item): bool
    {
        return WasteRecord::where('business_id', $item->business_id)
            ->where('inventory_item_id', $item->id)
            ->exists();
    }

    private function closestFacilities(array $coordinates, ?Item $item, ?array $analysis): array
    {
        if ($item === null) {
            return [];
        }

        return RecyclingCentreLocatorService::findNearby(
            $coordinates['lat'],
            $coordinates['lng'],
            $item->category,
            10,
            false,
            $analysis,
            $item->name ?: $item->title,
            maxDistanceKm: 100.0,
            closestFirst: true
        );
    }

    public function nearbyCentres(Request $request): JsonResponse
    {
        $this->checkApproved();

        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'category' => ['nullable', 'string', 'max:100'],
            'item_id' => ['nullable', 'integer'],
            'use_registered_address' => ['sometimes', 'boolean'],
            'include_related' => ['sometimes', 'boolean'],
        ]);

        $useRegisteredAddress = $request->boolean('use_registered_address');
        $includeRelated = $useRegisteredAddress && $request->boolean('include_related');
        $coordinates = $useRegisteredAddress
            ? RecyclingCentreLocatorService::getBusinessCoordinates($request->user())
            : ['lat' => (float) $validated['lat'], 'lng' => (float) $validated['lng']];

        $category = $validated['category'] ?? null;
        $analysisContext = null;
        $itemName = null;

        if (! empty($validated['item_id'])) {
            $item = Item::where('id', (int) $validated['item_id'])
                ->where('business_id', $request->user()->id)
                ->where('reusability', 'not_reusable')
                ->first();
            if ($item) {
                $analysisContext = $item->ai_analysis;
                $itemName = $item->name ?: $item->title;
                if (empty($category)) {
                    $category = $item->category;
                }
            }
        }

        $centres = RecyclingCentreLocatorService::findNearby(
            $coordinates['lat'],
            $coordinates['lng'],
            $category,
            12,
            ! $includeRelated,
            $analysisContext,
            $itemName,
            $useRegisteredAddress ? ($includeRelated ? 100.0 : 50.0) : null,
            $useRegisteredAddress ? $request->user()->state : null
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
