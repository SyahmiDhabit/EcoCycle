<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\User;
use App\Services\RecyclingCentreLocatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EcoCycleHelperAndGPSLocatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createApprovedBusiness(string $name = 'GreenTech Corp', string $city = 'Shah Alam', string $state = 'Selangor'): User
    {
        return User::factory()->create([
            'business_name' => $name,
            'account_status' => 'approved',
            'city' => $city,
            'state' => $state,
        ]);
    }

    private function createPendingBusiness(): User
    {
        return User::factory()->create([
            'business_name' => 'Pending Corp',
            'account_status' => 'pending',
            'city' => 'Klang',
            'state' => 'Selangor',
        ]);
    }

    public function test_unapproved_business_cannot_access_ecocycle_helper(): void
    {
        $pendingBusiness = $this->createPendingBusiness();

        $response = $this->actingAs($pendingBusiness)->get(route('business.helper'));

        $response->assertRedirect(route('business.pending'));
    }

    public function test_approved_business_can_access_ecocycle_helper(): void
    {
        $business = $this->createApprovedBusiness();

        $response = $this->actingAs($business)->get(route('business.helper'));

        $response->assertOk();
        $response->assertSee('EcoCycle Helper');
        $response->assertSee('AI Circular Advisor', false);
        $response->assertSee('GPS Locator');
    }

    public function test_ecocycle_helper_analyzes_item_when_item_id_provided(): void
    {
        $business = $this->createApprovedBusiness();

        $item = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Industrial Copper Scrap Wiring',
            'category' => 'Metals & machinery',
            'quantity' => 250,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 28.50,
            'condition' => 'Scrap / Cutoffs',
            'description' => 'Stripped copper wiring cable offcuts from industrial transformer rewiring.',
            'status' => 'in_stock',
        ]);

        $response = $this->actingAs($business)->get(route('business.helper', ['item_id' => $item->id]));

        $response->assertOk();
        $response->assertSee('Industrial Copper Scrap Wiring');
        $response->assertSee('Metals &amp; machinery', false);
    }

    public function test_ecocycle_helper_chat_endpoint_provides_ai_circular_responses(): void
    {
        $business = $this->createApprovedBusiness();

        $response = $this->actingAs($business)->postJson(route('business.helper.chat'), [
            'message' => 'What is the best way to recycle 300kg of clean HDPE plastic scrap in Malaysia?',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'reply',
            'suggested_questions',
        ]);

        $this->assertNotEmpty($response->json('reply'));
        $this->assertIsArray($response->json('suggested_questions'));
    }

    public function test_ecocycle_helper_analyze_api_endpoint(): void
    {
        $business = $this->createApprovedBusiness();

        $item = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Corrugated Cardboard Offcuts',
            'category' => 'Packaging & paper',
            'quantity' => 1200,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 0.45,
            'condition' => 'Scrap / Cutoffs',
            'status' => 'in_stock',
        ]);

        $response = $this->actingAs($business)->getJson(route('business.helper.analyze', $item->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'item',
            'analysis' => [
                'strategy',
                'primary_path',
                'action_recommendation',
                'repurpose_ideas',
                'recycling_steps',
                'disposal_warning',
                'estimated_value_myr',
                'carbon_saving_estimate',
                'compliance_note',
            ],
            'nearby_centres',
        ]);

        $this->assertSame('Corrugated Cardboard Offcuts', $response->json('item.name'));
    }

    public function test_nearby_centres_locator_with_gps_coordinates(): void
    {
        $business = $this->createApprovedBusiness();

        // Coordinates near Shah Alam
        $response = $this->actingAs($business)->postJson(route('business.helper.nearby'), [
            'lat' => 3.0738,
            'lng' => 101.5183,
            'category' => 'Plastics & rubber',
            'limit' => 5,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'centres' => [
                '*' => [
                    'id',
                    'name',
                    'category',
                    'accepted_materials',
                    'address',
                    'city',
                    'state',
                    'distance_km',
                    'directions_url',
                ],
            ],
        ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('centres'));
    }

    public function test_marketplace_reusable_and_non_reusable_tabs_segregation(): void
    {
        $seller = $this->createApprovedBusiness('Eco Supplies Sdn Bhd');

        // 1. Reusable item
        $reusableItem = InventoryItem::create([
            'business_id' => $seller->id,
            'name' => 'Heavy Duty Euro Pallets',
            'title' => 'Heavy Duty Euro Pallets',
            'category' => 'Packaging & paper',
            'quantity' => 80,
            'unit' => 'units',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 25.00,
            'condition' => 'Used - Good',
            'status' => 'active',
        ]);

        // 2. Non-reusable scrap item
        $scrapItem = InventoryItem::create([
            'business_id' => $seller->id,
            'name' => 'Mixed Industrial Plastic Shreds',
            'title' => 'Mixed Industrial Plastic Shreds',
            'category' => 'Plastics & rubber',
            'quantity' => 500,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 1.20,
            'condition' => 'Scrap / Cutoffs',
            'status' => 'active',
        ]);

        $buyer = $this->createApprovedBusiness('Buyer Corp');

        // Check default reusable tab
        $reusableTabResponse = $this->actingAs($buyer)->get(route('business.marketplace', ['tab' => 'reusable']));
        $reusableTabResponse->assertOk();
        $reusableTabResponse->assertSee('Heavy Duty Euro Pallets');
        $reusableTabResponse->assertDontSee('Mixed Industrial Plastic Shreds');

        // Check non-reusable tab
        $nonReusableTabResponse = $this->actingAs($buyer)->get(route('business.marketplace', ['tab' => 'non_reusable']));
        $nonReusableTabResponse->assertOk();
        $nonReusableTabResponse->assertSee('Mixed Industrial Plastic Shreds');
        $nonReusableTabResponse->assertDontSee('Heavy Duty Euro Pallets');
        $nonReusableTabResponse->assertSee('Ask Helper');
    }

    public function test_inventory_supports_table_and_catalog_grid_views(): void
    {
        $business = $this->createApprovedBusiness();

        InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Aluminum Offcut Sheets 3mm',
            'category' => 'Metals & machinery',
            'quantity' => 320,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 8.50,
            'condition' => 'Brand New',
            'status' => 'in_stock',
        ]);

        // Access catalog view mode
        $responseCatalog = $this->actingAs($business)->get(route('business.inventory', ['view' => 'catalog']));
        $responseCatalog->assertOk();
        $responseCatalog->assertSee('Catalog Grid');
        $responseCatalog->assertSee('Table');
        $responseCatalog->assertSee('inventory-catalog-container', false);
        $responseCatalog->assertSee('Aluminum Offcut Sheets 3mm');

        // Access table view mode
        $responseTable = $this->actingAs($business)->get(route('business.inventory', ['view' => 'table']));
        $responseTable->assertOk();
        $responseTable->assertSee('inventory-table-container', false);
        $responseTable->assertSee('Aluminum Offcut Sheets 3mm');
    }
}
