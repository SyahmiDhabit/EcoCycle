<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\WasteRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WastageMonitoringController extends Controller
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
            return redirect()->route('business.dashboard');
        }

        $query = WasteRecord::where('business_id', $business->id)->with('inventoryItem')->latest('recorded_at');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('material_name', 'like', "%{$search}%")
                  ->orWhere('waste_type', 'like', "%{$search}%")
                  ->orWhere('facility_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('waste_type')) {
            $query->where('waste_type', $type);
        }

        $records = $query->paginate(15)->withQueryString();

        // Calculate KPIs
        $allRecords = WasteRecord::where('business_id', $business->id)->get();
        $totalScrapGeneratedKg = $allRecords->sum('quantity');
        
        $divertedRecords = $allRecords->filter(function ($r) {
            return $r->is_diverted || in_array($r->status, ['sent_to_facility', 'recycled', 'composted']);
        });
        $totalDivertedKg = $divertedRecords->sum('quantity');

        $pendingRecords = $allRecords->filter(function ($r) {
            return in_array($r->status, ['in_storage', 'scheduled_pickup']);
        });
        $totalPendingKg = $pendingRecords->sum('quantity');

        $diversionRate = $totalScrapGeneratedKg > 0 ? round(($totalDivertedKg / $totalScrapGeneratedKg) * 100, 1) : 0;
        $totalSavingsRm = $allRecords->sum('cost_or_savings') ?: 0;

        // Categories list for filter
        $categories = $allRecords->pluck('waste_type')->unique()->filter()->values();

        // Non-reusable inventory items ready for quick logging
        $notReusableInventory = $business->inventoryItems()
            ->where('reusability', 'not_reusable')
            ->orderBy('name')
            ->get();

        return view('business.waste.index', [
            'business' => $business,
            'records' => $records,
            'totalScrapGeneratedKg' => $totalScrapGeneratedKg,
            'totalDivertedKg' => $totalDivertedKg,
            'totalPendingKg' => $totalPendingKg,
            'diversionRate' => $diversionRate,
            'totalSavingsRm' => $totalSavingsRm,
            'categories' => $categories,
            'notReusableInventory' => $notReusableInventory,
            'selectedStatus' => $status,
            'selectedCategory' => $type,
            'search' => $search,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $validated = $request->validate([
            'inventory_item_id' => ['nullable', 'exists:listing,id'],
            'material_name' => ['required', 'string', 'max:255'],
            'waste_type' => ['required', 'string', 'max:60'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['required', 'string', 'max:20'],
            'status' => ['required', 'in:in_storage,scheduled_pickup,sent_to_facility,recycled,composted'],
            'facility_name' => ['nullable', 'string', 'max:150'],
            'facility_location' => ['nullable', 'string', 'max:100'],
            'disposal_method' => ['required', 'string', 'max:60'],
            'ai_recommendation' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'recorded_at' => ['required', 'date'],
        ]);

        $isDiverted = in_array($validated['status'], ['sent_to_facility', 'recycled', 'composted']);

        $record = WasteRecord::create([
            'business_id' => $business->id,
            'inventory_item_id' => $validated['inventory_item_id'] ?? null,
            'material_name' => $validated['material_name'],
            'waste_type' => $validated['waste_type'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'status' => $validated['status'],
            'facility_name' => $validated['facility_name'] ?? null,
            'facility_location' => $validated['facility_location'] ?? null,
            'disposal_method' => $validated['disposal_method'],
            'is_diverted' => $isDiverted,
            'ai_recommendation' => $validated['ai_recommendation'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_at' => $validated['recorded_at'],
        ]);

        BusinessNotification::send(
            $business->id,
            'Wastage Record Logged',
            "Recorded {$validated['quantity']} {$validated['unit']} of {$validated['material_name']} under status: {$validated['status']}.",
            '/business/waste-monitoring'
        );

        return redirect()->route('business.waste.index')->with('status', 'Wastage entry successfully recorded.');
    }

    public function updateStatus(Request $request, WasteRecord $record): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();
        abort_unless($record->business_id === $business->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:in_storage,scheduled_pickup,sent_to_facility,recycled,composted'],
            'facility_name' => ['nullable', 'string', 'max:150'],
            'facility_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $record->status = $validated['status'];
        if (!empty($validated['facility_name'])) {
            $record->facility_name = $validated['facility_name'];
        }
        if (!empty($validated['facility_location'])) {
            $record->facility_location = $validated['facility_location'];
        }
        if (!empty($validated['notes'])) {
            $record->notes = $validated['notes'];
        }
        $record->is_diverted = in_array($validated['status'], ['sent_to_facility', 'recycled', 'composted']);
        $record->save();

        return redirect()->route('business.waste.index')->with('status', "Status updated to: {$record->status}");
    }

    public function logFromInventory(Request $request, InventoryItem $item): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();
        abort_unless($item->business_id === $business->id, 403);

        $qty = (float) $request->input('quantity', $item->quantity);
        $facilityName = $request->input('facility_name', 'Licensed Recovery Facility');
        $facilityLocation = $request->input('facility_location', $business->state ?: 'Selangor');
        $recommendation = $request->input('ai_recommendation', 'Routed via EcoCycle AI Scrap Advisor');

        WasteRecord::create([
            'business_id' => $business->id,
            'inventory_item_id' => $item->id,
            'material_name' => $item->name,
            'waste_type' => $item->category,
            'quantity' => $qty,
            'unit' => $item->unit,
            'status' => 'scheduled_pickup',
            'facility_name' => $facilityName,
            'facility_location' => $facilityLocation,
            'disposal_method' => 'Circular Material Recovery / Recycling',
            'is_diverted' => true,
            'ai_recommendation' => $recommendation,
            'notes' => 'Imported from internal inventory scrap tracker.',
            'recorded_at' => now()->toDateString(),
        ]);

        BusinessNotification::send(
            $business->id,
            'Item Routed to Wastage Monitoring',
            "{$item->name} has been routed to Wastage Monitoring for scheduled recovery.",
            '/business/waste-monitoring'
        );

        return redirect()->route('business.waste.index')->with('status', "'{$item->name}' has been routed to Wastage Monitoring.");
    }

    public function destroy(WasteRecord $record): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();
        abort_unless($record->business_id === $business->id, 403);

        $record->delete();

        return redirect()->route('business.waste.index')->with('status', 'Wastage record removed.');
    }
}
