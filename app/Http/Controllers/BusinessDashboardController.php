<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BusinessDashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        return $this->index($request);
    }

    public function index(Request $request): View|RedirectResponse
    {
        /** @var User|null $business */
        $business = Auth::guard('web')->user() ?: Auth::user();

        if (! $business) {
            if (Auth::guard('admin')->check()) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('login');
        }

        if ($business->account_status !== 'approved') {
            return view('business.verification', ['business' => $business]);
        }

        // Fetch approved businesses from database including current business
        // Eager-loading active marketplace listings and inventory items
        $dbApprovedUsers = User::query()
            ->where('account_status', 'approved')
            ->with(['marketplaceListings' => function ($q) {
                $q->where('status', 'active');
            }])
            ->with('inventoryItems')
            ->latest()
            ->get();

        $businesses = [];
        $industriesMap = [];
        $locationsMap = [];
        $materialsMap = [];

        foreach ($dbApprovedUsers as $u) {
            if (!empty($u->industry)) {
                $industriesMap[$u->industry] = true;
            }
            if (!empty($u->state)) {
                $locationsMap[$u->state] = true;
            }

            $listings = $u->marketplaceListings;
            $invItems = $u->inventoryItems;

            $cats = $listings->pluck('category')->merge($invItems->pluck('category'))->unique()->filter()->values()->all();
            foreach ($cats as $cat) {
                $materialsMap[$cat] = true;
            }

            $materialsStr = !empty($cats) ? implode(' · ', array_slice($cats, 0, 3)) : ($u->industry ?: 'Materials on request');

            // Find image if available from profile_image, listings, or profile doc
            $image = null;
            if (!empty($u->profile_image)) {
                $image = $u->profile_image_url;
            } else {
                $firstWithImage = $listings->firstWhere('image_path', '!=', null);
                if ($firstWithImage && !empty($firstWithImage->image_path)) {
                    $image = $firstWithImage->image_path;
                } elseif (!empty($u->ssm_profile_path) && preg_match('/\.(jpg|jpeg|png|webp)$/i', $u->ssm_profile_path)) {
                    $image = '/business/application/documents/profile';
                }
            }

            $locationParts = array_filter([$u->city, $u->state]);
            $locationStr = !empty($locationParts) ? implode(', ', $locationParts) : 'Malaysia';

            $formattedListings = $listings->map(function ($l) {
                return [
                    'id' => $l->id,
                    'title' => $l->title,
                    'description' => $l->description,
                    'category' => $l->category,
                    'quantity' => (float) $l->quantity,
                    'unit' => $l->unit,
                    'price' => $l->price ? number_format((float) $l->price, 2) : '0.00',
                    'is_free' => (bool) $l->is_free,
                    'condition' => $l->condition ?: 'Good surplus',
                    'image' => $l->image_path ?: null,
                ];
            })->values()->all();

            $businesses[] = [
                'id' => $u->id,
                'business_name' => $u->business_name ?: $u->name,
                'name' => $u->name,
                'is_current_user' => ($u->id === $business->id),
                'industry' => $u->industry ?: 'Not specified',
                'city' => $u->city ?: '',
                'state' => $u->state ?: '',
                'location' => $locationStr,
                'registered_address' => $u->registered_address ?: 'Not specified',
                'description' => $u->registered_address ? ('Registered facility in ' . $locationStr . '.') : 'Verified EcoCycle partner.',
                'materials' => $materialsStr,
                'listings_count' => $listings->count(),
                'image' => $image,
                'registration_no' => $u->business_registration_number ?: 'Verified on SSM',
                'phone' => $u->business_phone ?: 'Not provided',
                'email' => $u->email,
                'listings' => $formattedListings,
            ];
        }

        $availableIndustries = array_keys($industriesMap);
        sort($availableIndustries);
        $availableLocations = array_keys($locationsMap);
        sort($availableLocations);
        $availableMaterials = array_keys($materialsMap);
        sort($availableMaterials);

        return view('business.dashboard', [
            'business' => $business,
            'trackedItemsCount' => 0,
            'materialsListedCount' => 0,
            'wasteDivertedKg' => 0.0,
            'exchangeImpactRm' => 0.0,
            'approvedCount' => count($businesses),
            'businesses' => $businesses,
            'availableIndustries' => $availableIndustries,
            'availableLocations' => $availableLocations,
            'availableMaterials' => $availableMaterials,
        ]);
    }

    public function show(User $company): View
    {
        /** @var User $business */
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return view('business.verification', ['business' => $business]);
        }

        abort_unless($company->account_status === 'approved', 404, 'Business profile not found.');

        $company->load([
            'marketplaceListings' => function ($q) {
                $q->where('status', 'active')->latest();
            },
            'inventoryItems',
        ]);

        return view('business.directory.show', [
            'business' => $business,
            'company' => $company,
            'listings' => $company->marketplaceListings,
        ]);
    }
}
