<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceReview;
use App\Models\MaterialExchange;
use App\Models\User;
use App\Models\WasteRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessFullSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createApprovedBusiness(string $name = 'Alpha Tech Sdn Bhd'): User
    {
        return User::factory()->create([
            'business_name' => $name,
            'account_status' => 'approved',
            'city' => 'Shah Alam',
            'state' => 'Selangor',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_can_route_not_reusable_item_from_inventory_to_wastage_monitoring(): void
    {
        $biz = $this->createApprovedBusiness();
        $item = InventoryItem::create([
            'business_id' => $biz->id,
            'name' => 'Steel Machine Shavings',
            'category' => 'Metal & fabrication',
            'quantity' => 200,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'status' => 'in_stock',
        ]);

        $response = $this->actingAs($biz)
            ->post("/business/waste-monitoring/from-inventory/{$item->id}", [
                'quantity' => 200,
                'facility_name' => 'Shan Poornam Metals Sdn Bhd',
                'facility_location' => 'Penang',
            ]);

        $response->assertRedirect('/business/waste-monitoring');

        $this->assertDatabaseHas('waste_record', [
            'business_id' => $biz->id,
            'inventory_item_id' => $item->id,
            'material_name' => 'Steel Machine Shavings',
            'facility_name' => 'Shan Poornam Metals Sdn Bhd',
            'status' => 'scheduled_pickup',
            'is_diverted' => true,
        ]);
    }

    public function test_waste_monitoring_disables_and_rejects_inventory_already_logged(): void
    {
        $business = $this->createApprovedBusiness();
        $item = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Paper offcuts',
            'category' => 'Other',
            'quantity' => 2,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'status' => 'in_stock',
        ]);
        $payload = [
            'inventory_item_id' => $item->id,
            'material_name' => $item->name,
            'waste_type' => 'Other',
            'quantity' => 2,
            'unit' => 'kg',
            'status' => 'in_storage',
            'recorded_at' => now()->toDateString(),
        ];

        $this->actingAs($business)->post(route('business.waste.store'), $payload)
            ->assertRedirect(route('business.waste.index'));
        $this->get(route('business.waste.index'))->assertOk()
            ->assertSee('value="'.$item->id.'" disabled', false)
            ->assertSee('Already logged');
        $this->post(route('business.waste.store'), $payload)->assertSessionHasErrors('inventory_item_id');
        $this->assertDatabaseCount('waste_record', 1);
    }

    public function test_wastage_monitoring_page_and_status_update(): void
    {
        $biz = $this->createApprovedBusiness();
        $record = WasteRecord::create([
            'business_id' => $biz->id,
            'material_name' => 'Broken Pallets',
            'waste_type' => 'Furniture & wood products',
            'quantity' => 500,
            'unit' => 'kg',
            'status' => 'in_storage',
            'is_diverted' => false,
            'recorded_at' => now()->toDateString(),
        ]);

        $response = $this->actingAs($biz)->get('/business/waste-monitoring');
        $response->assertStatus(200);
        $response->assertSee('Broken Pallets');
        $response->assertSee('500');

        $updateResponse = $this->actingAs($biz)
            ->put("/business/waste-monitoring/{$record->id}", [
                'status' => 'recycled',
                'facility_payment_rm' => '25.50',
                'facility_name' => 'Merces Bio-Pelletizing Facility',
                'facility_location' => 'Rawang, Selangor',
            ]);

        $updateResponse->assertRedirect('/business/waste-monitoring');
        $this->put(route('business.waste.update', $record), ['status' => 'in_storage'])->assertConflict();
        $this->delete(route('business.waste.destroy', $record))->assertConflict();
        $this->get(route('business.waste.report', $record))->assertOk();
        $this->assertDatabaseHas('waste_record', [
            'id' => $record->id,
            'status' => 'recycled',
            'is_diverted' => true,
        ]);
    }

    public function test_waste_monitoring_shows_filterable_records_without_summary_cards(): void
    {
        $business = $this->createApprovedBusiness();

        foreach ([
            [17, 'kg', 'in_storage', false],
            [3000, 'g', 'recycled', true],
            [250, 'mL', 'scheduled_pickup', true],
            [0.75, 'L', 'sent_to_facility', true],
            [250, 'mL', 'composted', true],
            [2, 'pcs', 'in_storage', false],
        ] as [$quantity, $unit, $status, $diverted]) {
            WasteRecord::create([
                'business_id' => $business->id,
                'material_name' => 'Scrap batch',
                'waste_type' => 'Other',
                'quantity' => $quantity,
                'unit' => $unit,
                'status' => $status,
                'is_diverted' => $diverted,
                'recorded_at' => now()->toDateString(),
            ]);
        }

        $this->actingAs($business)->get(route('business.waste.index'))->assertOk()
            ->assertSee('Scrap batch')
            ->assertDontSee('Summary unit')
            ->assertDontSee('Scrap Recorded by Unit')
            ->assertDontSee('Scrap Processing Outcomes')
            ->assertDontSee('Waste Handover Progress');

        $this->get(route('business.waste.index', ['status' => 'composted']))->assertOk()
            ->assertSee('Showing 1 scrap logs');
    }


    public function test_transactions_flow_request_payment_and_mark_paid(): void
    {
        $seller = $this->createApprovedBusiness('Seller Timber Hub');
        $buyer = $this->createApprovedBusiness('Buyer Furniture Works');

        $listing = MarketplaceListing::create([
            'business_id' => $seller->id,
            'title' => 'Oak Offcuts',
            'description' => 'Solid kiln-dried oak offcuts suitable for carpentry.',
            'category' => 'Furniture & wood products',
            'quantity' => 10,
            'unit' => 'pcs',
            'price' => 50.00,
            'listing_type' => 'buy',
            'reusability' => 'reusable',
            'status' => 'active',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
        ]);

        $exchange = MaterialExchange::create([
            'item_id' => $listing->id,
            'provider_business_id' => $seller->id,
            'requester_business_id' => $buyer->id,
            'quantity' => 10,
            'unit' => 'pcs',
            'total_price' => 500.00,
            'status' => 'accepted',
            'payment_status' => 'pending_payment',
        ]);

        // Seller requests payment
        $reqResponse = $this->actingAs($seller)
            ->post("/business/transactions/{$exchange->id}/request-payment");
        
        $reqResponse->assertRedirect();
        $this->assertDatabaseHas('material_exchange', [
            'id' => $exchange->id,
            'payment_status' => 'requested',
        ]);

        // Buyer or seller marks paid
        $paidResponse = $this->actingAs($buyer)
            ->post("/business/transactions/{$exchange->id}/mark-paid", [
                'payment_reference' => 'FPX-TEST-9988',
            ]);

        $paidResponse->assertRedirect();
        $this->assertDatabaseHas('material_exchange', [
            'id' => $exchange->id,
            'payment_status' => 'paid',
            'payment_reference' => 'FPX-TEST-9988',
            'status' => 'confirmed',
            'delivery_status' => 'confirmed',
        ]);
        $this->assertSame(0.0, (float) $listing->fresh()->quantity);
        $this->assertSame('sold', $listing->fresh()->status);
    }

    public function test_my_listings_page_and_review_feedback(): void
    {
        $seller = $this->createApprovedBusiness('Seller Plastics');
        $buyer = $this->createApprovedBusiness('Buyer Molding');

        $listing = MarketplaceListing::create([
            'business_id' => $seller->id,
            'title' => 'Clean HDPE Regrind',
            'description' => 'Industrial post-manufacturing clean HDPE regrind.',
            'category' => 'Plastics & rubber',
            'quantity' => 100,
            'unit' => 'kg',
            'price' => 200.00,
            'listing_type' => 'buy',
            'reusability' => 'reusable',
            'status' => 'active',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
        ]);

        $pageResponse = $this->actingAs($seller)->get('/business/my-listings');
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Clean HDPE Regrind');

        // Toggle status to sold
        $statusResponse = $this->actingAs($seller)
            ->post("/business/my-listings/{$listing->id}/status", [
                'status' => 'sold',
            ]);
        $statusResponse->assertRedirect();
        $this->assertDatabaseHas('item', [
            'id' => $listing->id,
            'status' => 'sold',
        ]);

        // Buyer leaves review
        $reviewResponse = $this->actingAs($buyer)
            ->post("/business/my-listings/{$listing->id}/reviews", [
                'rating' => 5,
                'comment' => 'High purity material, reliable seller!',
                'exchange_type' => 'buy',
            ]);
        $reviewResponse->assertRedirect();
        $this->assertDatabaseHas('marketplace_review', [
            'item_id' => $listing->id,
            'seller_business_id' => $seller->id,
            'reviewer_business_id' => $buyer->id,
            'rating' => 5,
        ]);
    }

    public function test_reports_page_charts_paid_transactions_and_counts_waste_records(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 25)->startOfDay());
        $business = $this->createApprovedBusiness('Green Paper Works');
        $partner = $this->createApprovedBusiness('Recovery Partner');
        $listing = MarketplaceListing::create([
            'business_id' => $business->id,
            'title' => 'Oak Offcuts',
            'category' => 'Wood',
            'quantity' => 10,
            'unit' => 'kg',
            'price' => 25,
            'status' => 'active',
        ]);

        foreach (['paid' => 125, 'pending' => 500] as $status => $price) {
            MaterialExchange::create([
                'item_id' => $listing->id,
                'provider_business_id' => $business->id,
                'requester_business_id' => $partner->id,
                'quantity' => 5,
                'unit' => 'kg',
                'total_price' => $price,
                'status' => $status === 'paid' ? 'completed' : 'accepted',
                'completed_at' => $status === 'paid' ? now() : null,
                'payment_status' => $status,
            ]);
        }

        foreach (['kg' => 'in_storage', 'litre' => 'recycled'] as $unit => $status) {
            WasteRecord::create([
                'business_id' => $business->id,
                'material_name' => 'Production waste',
                'waste_type' => 'Mixed waste',
                'quantity' => 100,
                'unit' => $unit,
                'status' => $status,
                'recorded_at' => now()->toDateString(),
            ]);
        }
        WasteRecord::create([
            'business_id' => $partner->id,
            'material_name' => 'Other business record',
            'waste_type' => 'Mixed waste',
            'quantity' => 100,
            'unit' => 'kg',
            'status' => 'recycled',
            'recorded_at' => now()->toDateString(),
        ]);

        $this->actingAs($business)->get('/business/reports')
            ->assertOk()
            ->assertViewHas('totalSalesRevenue', 125.0)
            ->assertViewHas('salesTrend', fn (array $trend): bool => $trend['25 Nov 2026'] === 125.0 && $trend['24 Nov 2026'] === 0.0)
            ->assertViewHas('purchaseTrend', fn (array $trend): bool => $trend['25 Nov 2026'] === 0.0 && $trend['24 Nov 2026'] === 0.0)
            ->assertSee('Sales revenue over time')
            ->assertSee('Total purchases over time')
            ->assertViewHas('period', 'week')
            ->assertDontSee('report-period-description')
            ->assertViewHas('totalSalesOrders', 1)
            ->assertViewHas('totalPurchasesSpent', 0.0)
            ->assertViewHas('wasteCategories', ['Mixed waste' => 2])
            ->assertViewHas('wasteStatuses', fn (array $statuses): bool => $statuses['In storage'] === 1 && $statuses['Recycled'] === 1)
            ->assertSee('Waste records by status')
            ->assertDontSee('Other business record')
            ->assertViewHas('totalTransactions', 2)
            ->assertDontSee('Active Listings')
            ->assertViewHas('listingCategories', ['Wood' => 1])
            ->assertDontSee('Total Waste Recovered')
            ->assertDontSee('Not yet analysed')
            ->assertSee('Your role')
            ->assertSee('>Seller</td>', false)
            ->assertDontSee('Monthly listing performance')
            ->assertDontSee('Transaction performance');

        $this->get('/business/reports?period=day')
            ->assertOk()
            ->assertViewHas('trendInterval', 'hour')
            ->assertViewHas('salesTrend', fn (array $trend): bool => $trend['00:00'] === 125.0 && count($trend) === 24);

        $this->get('/business/reports?period=custom&from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->assertViewHas('totalItems', 0)
            ->assertViewHas('totalTransactions', 0)
            ->assertViewHas('totalSalesRevenue', 0.0)
            ->assertViewHas('wasteCategories', [])
            ->assertSee('No records for this period.');

        $this->get('/business/reports?period=custom&from=2026-11-26&to=2026-11-25')
            ->assertOk()
            ->assertSee('Showing this week.')
            ->assertViewHas('period', 'week');
    }

    public function test_transaction_views_scope_rows_and_summary_to_the_selected_role(): void
    {
        $business = $this->createApprovedBusiness('Trading Business');
        $partner = $this->createApprovedBusiness('Trading Partner');

        foreach ([
            [$business, $partner, 'Sold Offcuts', 125, 'completed', 'paid'],
            [$partner, $business, 'Purchased Paper', 40, 'accepted', 'pending'],
        ] as [$seller, $buyer, $title, $total, $status, $paymentStatus]) {
            $listing = MarketplaceListing::create([
                'business_id' => $seller->id,
                'title' => $title,
                'category' => 'Materials',
                'quantity' => 5,
                'unit' => 'kg',
                'price' => $total,
                'status' => 'active',
            ]);

            MaterialExchange::create([
                'item_id' => $listing->id,
                'item_name' => $title,
                'provider_business_id' => $seller->id,
                'requester_business_id' => $buyer->id,
                'quantity' => 1,
                'unit' => 'kg',
                'total_price' => $total,
                'status' => $status,
                'payment_status' => $paymentStatus,
            ]);
        }

        $this->actingAs($business)->get(route('business.transactions', ['tab' => 'sales']))
            ->assertOk()->assertSee('Sold Offcuts')->assertDontSee('Purchased Paper')
            ->assertSee('Total Sales')->assertDontSee('Total Purchases')
            ->assertViewHas('pendingPaymentsCount', 0)->assertViewHas('completedExchangesCount', 1);

        $this->get(route('business.transactions', ['tab' => 'purchases']))
            ->assertOk()->assertSee('Purchased Paper')->assertDontSee('Sold Offcuts')
            ->assertSee('Total Purchases')->assertDontSee('Total Sales')
            ->assertViewHas('pendingPaymentsCount', 1)->assertViewHas('completedExchangesCount', 0);

        $this->get(route('business.transactions'))
            ->assertOk()->assertSee('Sold Offcuts')->assertSee('Purchased Paper')
            ->assertViewHas('pendingPaymentsCount', 1)->assertViewHas('completedExchangesCount', 1);
    }

    public function test_profile_update_and_password_change(): void
    {
        $biz = $this->createApprovedBusiness('Original Name Corp');

        $updateResponse = $this->actingAs($biz)
            ->put('/business/profile', [
                'business_name' => 'Updated Name Corp',
                'business_registration_number' => '202601009999',
                'industry' => 'Manufacturing & Industrial',
                'business_phone' => '+60388889999',
                'registered_address' => 'Lot 10 Industrial Zone',
                'city' => 'Klang',
                'state' => 'Selangor',
                'postcode' => '41200',
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('business', [
            'id' => $biz->id,
            'business_name' => 'Updated Name Corp',
            'city' => 'Klang',
        ]);

        // Password change
        $passResponse = $this->actingAs($biz)
            ->put('/business/profile/password', [
                'current_password' => 'password123',
                'password' => 'newSecretPass!2026',
                'password_confirmation' => 'newSecretPass!2026',
            ]);

        $passResponse->assertRedirect();
        $biz->refresh();
        $this->assertTrue(Hash::check('newSecretPass!2026', $biz->password));
    }

    public function test_business_dashboard_displays_single_top_left_hamburger_and_slide_out_sidebar(): void
    {
        $biz = $this->createApprovedBusiness('Eco Slide Sidebar Corp');

        $response = $this->actingAs($biz)->get('/business');

        $response->assertOk();

        // Exactly ONE hamburger button on top-left
        $response->assertSee('id="sidebar-toggle-btn"', false);
        $response->assertSee('onclick="toggleSidebar()"', false);
        $response->assertSee('class="hamburger-icon-bars"', false);
        $response->assertDontSee('id="nav-drawer-toggle-btn"', false);
        $response->assertDontSee('id="business-right-drawer"', false);

        // Slide-out left sidebar & backdrop
        $response->assertSee('id="dashboard-sidebar"', false);
        $response->assertSee('id="sidebar-backdrop"', false);
        $response->assertSee('class="sidebar-close-btn"', false);
        $response->assertSee('onclick="closeSidebar()"', false);

        // Layer-by-layer collapsible business navigation groups & links inside the slide-out sidebar
        $response->assertSee('data-sidebar-group="workspace"', false);
        $response->assertSee('data-sidebar-group="inventory"', false);
        $response->assertSee('data-sidebar-group="marketplace"', false);
        $response->assertSee('data-sidebar-group="transactions"', false);
        $response->assertSee('data-sidebar-group="reviews"', false);
        $response->assertSee('aria-expanded="false"', false);
        $response->assertSee('toggleSidebarSubmenu(', false);
        $response->assertSee('href="/business"', false);
        $response->assertSee('href="/business/inventory"', false);
        $response->assertSee('href="/business/inventory?add=1"', false);
        $response->assertSee('href="/business/helper"', false);
        $response->assertSee('href="/business/marketplace"', false);
        $response->assertSee('href="/business/orders?tab=purchases"', false);
        $response->assertSee('href="/business/orders?tab=sales"', false);
        $response->assertSee('href="/business/my-listings"', false);
        $response->assertDontSee('href="/business/orders"', false);
        $response->assertSee('href="/business/transactions"', false);
        $response->assertSee('href="/business/invoices"', false);
        $response->assertSee('href="/business/reviews"', false);
        $response->assertSee('href="/business/reports"', false);
        $response->assertSee(route('business.profile'), false);
        $response->assertSee('<span>Sign Out</span>', false);
        $response->assertDontSee('Sign Out (Logout)');
        $response->assertDontSee('sidebar-user-card-wrap');
        $response->assertDontSee('sidebar-footer-card');
        $response->assertDontSee('sidebar-submenu open', false);
    }
}
