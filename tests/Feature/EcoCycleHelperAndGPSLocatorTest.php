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
        $response->assertSee('Material Analysis', false);
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

    public function test_gemini_api_integration_analyzes_material_when_api_key_is_configured(): void
    {
        $business = $this->createApprovedBusiness();

        config(['services.gemini.api_key' => 'fake-gemini-key']);
        config(['services.gemini.model' => 'gemini-1.5-flash']);

        \Illuminate\Support\Facades\Http::fake([
            'https://generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'strategy' => 'Gemini Optical Polymer Sorting & Pyrolysis',
                                        'primary_path' => 'Thermal Depolymerization',
                                        'action_recommendation' => 'Convert to circular pyrolysis oil',
                                        'repurpose_ideas' => ['Cracked virgin-grade polymer feed', 'Industrial fuel blend'],
                                        'recycling_steps' => ['Step 1: Shred', 'Step 2: Pyrolyze in oxygen-free retort'],
                                        'disposal_warning' => 'Avoid unmonitored incineration.',
                                        'estimated_value_myr' => 'RM 2.50 - RM 4.00 per kg',
                                        'carbon_saving_estimate' => '3.2 kg CO2e saved per kg',
                                        'compliance_note' => 'Licensed under DOE Clean Air and Scheduled Waste guidelines.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $item = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Polymer Film Roll Offcuts',
            'category' => 'Plastics & rubber',
            'quantity' => 450,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 1.50,
            'condition' => 'Scrap / Cutoffs',
            'status' => 'in_stock',
        ]);

        $response = $this->actingAs($business)->getJson(route('business.helper.analyze', $item->id));

        $response->assertOk();
        $this->assertSame('Gemini Optical Polymer Sorting & Pyrolysis', $response->json('analysis.strategy'));
        $this->assertSame('Thermal Depolymerization', $response->json('analysis.primary_path'));
        $this->assertSame('Google Gemini AI', $response->json('analysis.ai_source'));
    }

    public function test_gemini_api_integration_handles_chat_replies_when_api_key_is_configured(): void
    {
        $business = $this->createApprovedBusiness();

        config(['services.gemini.api_key' => 'fake-gemini-key']);
        config(['services.gemini.model' => 'gemini-1.5-flash']);

        \Illuminate\Support\Facades\Http::fake([
            'https://generativelanguage.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Based on Gemini circular analysis, industrial copper wire offcuts should be stripped and consolidated into 50kg bales for certified secondary smelters in Klang.',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($business)->postJson(route('business.helper.chat'), [
            'message' => 'What is the most cost-effective recovery method for copper cable scrap?',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $this->assertStringContainsString('Based on Gemini circular analysis', $response->json('reply'));
    }

    public function test_ecocycle_helper_strictly_excludes_reusable_items_and_renders_google_maps(): void
    {
        $business = $this->createApprovedBusiness();

        $reusableItem = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Reusable Steel Warehouse Racks',
            'category' => 'Metals & machinery',
            'quantity' => 15,
            'unit' => 'units',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 450.00,
            'condition' => 'Used - Good',
            'status' => 'in_stock',
        ]);

        $nonReusableItem = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Shattered Industrial Safety Glass',
            'category' => 'Construction & debris',
            'quantity' => 120,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'listing_type' => 'free',
            'price' => 0.00,
            'condition' => 'Damaged / Scrap',
            'status' => 'in_stock',
        ]);

        // 1. Helper page lists ONLY Non-Reusable items, renders interactive GPS map and no chatbox
        $indexResponse = $this->actingAs($business)->get(route('business.helper'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Shattered Industrial Safety Glass');
        $indexResponse->assertDontSee('Reusable Steel Warehouse Racks');
        $indexResponse->assertSee('id="ecocycle-gps-map"', false);
        $indexResponse->assertDontSee('id="helper-chat-panel"', false);
        $indexResponse->assertDontSee('id="chat-form"', false);

        // 2. Passing a Reusable item_id via query string is ignored and not displayed
        $reusableQueryResponse = $this->actingAs($business)->get(route('business.helper', ['item_id' => $reusableItem->id]));
        $reusableQueryResponse->assertOk();
        $reusableQueryResponse->assertDontSee('Reusable Steel Warehouse Racks');
        $reusableQueryResponse->assertSee('Select a Non-Reusable Item');

        // 3. Direct analyze API endpoint rejects Reusable items with HTTP 422
        $analyzeReusableResponse = $this->actingAs($business)->getJson(route('business.helper.analyze', $reusableItem->id));
        $analyzeReusableResponse->assertStatus(422);
        $analyzeReusableResponse->assertJson([
            'success' => false,
        ]);

        // 4. Direct analyze API endpoint accepts Non-Reusable items
        $analyzeNonReusableResponse = $this->actingAs($business)->getJson(route('business.helper.analyze', $nonReusableItem->id));
        $analyzeNonReusableResponse->assertOk();
        $this->assertSame('Shattered Industrial Safety Glass', $analyzeNonReusableResponse->json('item.name'));
    }

    public function test_ecocycle_helper_uses_sme_company_address_as_gps_reference_and_carries_stored_ai_analysis(): void
    {
        $business = User::factory()->create([
            'business_name' => 'Apex Manufacturing Sdn Bhd',
            'account_status' => 'approved',
            'registered_address' => 'Lot 45, Kawasan Perindustrian Bukit Raja, Jalan Meru',
            'city' => 'Klang',
            'postcode' => '41050',
            'state' => 'Selangor',
        ]);

        $storedAnalysis = [
            'strategy' => 'Certified Secondary Smelting & Thermal Recovery',
            'action_recommendation' => 'Segregate heavy steel offcuts from light scrap',
            'owner_actions' => [
                'Bundle offcuts in steel bins with SWCorp certified labelling',
                'Schedule authorized industrial hauler pickup for Klang scrap depot',
            ],
            'repurpose_ideas' => ['Direct blast furnace feed for rebar manufacturing'],
            'environmental_impact' => 'Prevents heavy metal leaching and conserves raw iron ore mining.',
            'carbon_impact' => 'Saves approx 1.8 tons CO2e per ton of recovered secondary steel.',
            'improper_disposal_risks' => 'Dumping heavy industrial metal violates DOE Environmental Quality Act.',
            'estimated_value_myr' => 'RM 1.20 - RM 1.80 per kg',
        ];

        $nonReusableItem = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Heavy Structural Steel Offcuts',
            'category' => 'Metals & machinery',
            'quantity' => 1500,
            'unit' => 'kg',
            'condition' => 'Scrap / Cutoffs',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 1.50,
            'ai_analysis' => $storedAnalysis,
            'status' => 'in_stock',
        ]);

        // 1. Visit EcoCycle Helper - verifies registered company address is displayed as GPS reference
        $response = $this->actingAs($business)->get(route('business.helper', ['item_id' => $nonReusableItem->id]));

        $response->assertOk();
        // SME Registered Address reference & My Address button
        $response->assertSee('Apex Manufacturing Sdn Bhd');
        $response->assertSee('Klang');
        $response->assertSee('41050');
        $response->assertSee('My Address');
        $response->assertSee('Registered Company Address (Main Reference)');

        // Carried-forward AI analysis results from Add New Inventory (NO re-analysis)
        $response->assertSee('Certified Secondary Smelting &amp; Thermal Recovery', false);
        $response->assertSee('Segregate heavy steel offcuts from light scrap');
        $response->assertSee('Bundle offcuts in steel bins with SWCorp certified labelling');
        $response->assertSee('Direct blast furnace feed for rebar manufacturing');
        $response->assertSee('Prevents heavy metal leaching and conserves raw iron ore mining.');

        // NO chatbot elements
        $response->assertDontSee('id="helper-chat-panel"', false);
        $response->assertDontSee('id="chat-form"', false);
        $response->assertDontSee('id="chat-input"', false);

        // Interactive GPS Map and multiple suitable nearby facilities prioritised by suitability
        $response->assertSee('id="ecocycle-gps-map"', false);
        $response->assertSee('Shan Poornam Metals (Selangor Regional Depot)');
        $response->assertSee('Southern Steel Circular Recovery Hub');
        $response->assertSee('from My Address');
        $response->assertSee('Most Suitable Match');

        // 2. Direct API call to analyze endpoint returns stored AI analysis directly
        $apiResponse = $this->actingAs($business)->getJson(route('business.helper.analyze', $nonReusableItem->id));
        $apiResponse->assertOk();
        $this->assertSame('Certified Secondary Smelting & Thermal Recovery', $apiResponse->json('analysis.strategy'));
        $this->assertSame('Segregate heavy steel offcuts from light scrap', $apiResponse->json('analysis.action_recommendation'));
        $this->assertNotEmpty($apiResponse->json('nearby_centres'));
        $this->assertGreaterThanOrEqual(2, count($apiResponse->json('nearby_centres')));
    }

    public function test_ecocycle_helper_my_address_prioritises_most_suitable_locations_based_on_ai_analysis_and_item_type(): void
    {
        $business = User::factory()->create([
            'business_name' => 'Mega Electronics Assembly Sdn Bhd',
            'account_status' => 'approved',
            'registered_address' => 'Persiaran Jubli Perak, Seksyen 22',
            'city' => 'Shah Alam',
            'postcode' => '40300',
            'state' => 'Selangor',
        ]);

        $analysis = [
            'strategy' => 'Certified Hydrometallurgical Copper & Precious Metals Extraction',
            'action_recommendation' => 'Bundle defective PCBs and stripped copper wiring cables for licensed e-waste recovery',
            'owner_actions' => [
                'De-solder heavy components and pack circuit boards in anti-static ESD crates',
                'Deliver to DOE licensed SW110 e-waste recovery facility in Shah Alam',
            ],
            'repurpose_ideas' => ['Hydrometallurgical gold, silver, and high-grade copper cathode extraction'],
            'improper_disposal_risks' => 'Heavy metals leaching into stormwater drains violates DOE regulations.',
            'estimated_value_myr' => 'RM 15.00 - RM 30.00 per kg',
        ];

        $eWasteItem = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Defective Server Motherboards and Stripped Copper Cables',
            'category' => 'Electronics',
            'quantity' => 450,
            'unit' => 'kg',
            'condition' => 'Scrap / Cutoffs',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 18.00,
            'ai_analysis' => $analysis,
            'status' => 'in_stock',
        ]);

        // Request helper page with this non-reusable item
        $response = $this->actingAs($business)->get(route('business.helper', ['item_id' => $eWasteItem->id]));
        $response->assertOk();

        // Verifies My Address reference point
        $response->assertSee('Mega Electronics Assembly Sdn Bhd');
        $response->assertSee('Shah Alam');
        $response->assertSee('My Address');

        // Verifies multiple suitable places are found and prioritised
        $response->assertSee('TES-AMM Malaysia Circular Electronics Recovery');
        $response->assertSee('Jalan Ragum 15/17, Seksyen 15, 40200 Shah Alam, Selangor');
        $response->assertSee('Shan Poornam Metals');

        // Verifies suitability tier/badge
        $response->assertSee('Most Suitable Match');

        // Call nearby-centres API directly with item_id to confirm suitability-first ranking
        $coords = RecyclingCentreLocatorService::getBusinessCoordinates($business);
        $apiResponse = $this->actingAs($business)->postJson(route('business.helper.nearby'), [
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
            'category' => 'Electronics',
            'item_id' => $eWasteItem->id,
        ]);

        $apiResponse->assertOk();
        $centres = $apiResponse->json('centres');
        $this->assertNotEmpty($centres);
        $this->assertGreaterThanOrEqual(2, count($centres));

        // Top ranked centre must be a specialised electronic recovery facility
        $topCentre = $centres[0];
        $this->assertSame('Most Suitable', $topCentre['suitability_tier']);
        $this->assertNotEmpty($topCentre['address']);
        $this->assertNotEmpty($topCentre['name']);
        $this->assertNotNull($topCentre['lat']);
        $this->assertNotNull($topCentre['lng']);
        $this->assertStringContainsString('Electronics', json_encode($topCentre['accepted_materials']));
    }
}
