<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Item;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryGeminiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createApprovedBusiness(string $name = 'EcoTech Circular Sdn Bhd', string $city = 'Shah Alam', string $state = 'Selangor'): User
    {
        return User::factory()->create([
            'business_name' => $name,
            'account_status' => 'approved',
            'city' => $city,
            'state' => $state,
            'registered_address' => 'No. 12, Jalan Perindustrian 1, Seksyen 15',
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeGeminiSuccess(array $overrides = []): void
    {
        config()->set('services.gemini.api_key', 'test-gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        $payload = array_merge([
            'suggested_category' => 'Solid Oak Timber Planks',
            'category_confidence' => 'high',
            'suggested_reusability' => 'reusable',
            'classification_label' => 'Reusable',
            'reason' => 'Visual inspection of the uploaded photos confirms structurally intact timber planks suitable for reuse.',
            'recommended_actions' => [
                'Store in dry inventory and publish to EcoCycle Marketplace for reuse.',
            ],
            'recycling_recommendations' => 'Chip damaged offcuts for composite board manufacturing.',
            'repurposing_ideas' => ['Furniture joinery', 'Architectural cladding'],
            'disposal_method' => 'Send clean offcuts to a licensed biomass recovery center.',
        ], $overrides);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($payload)],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    public function test_ai_analyze_endpoint_requires_approved_business(): void
    {
        $guestResponse = $this->postJson(route('business.inventory.ai-analyze'), []);
        $guestResponse->assertUnauthorized();

        $pendingBusiness = $this->createPendingBusiness();
        $pendingResponse = $this->actingAs($pendingBusiness)->postJson(route('business.inventory.ai-analyze'), []);
        $pendingResponse->assertForbidden();
    }

    public function test_ai_analyze_validates_required_fields_dropdowns_and_photo_limits(): void
    {
        $business = $this->createApprovedBusiness();

        // 1. Missing all fields
        $response = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'description', 'quantity', 'unit', 'condition', 'images']);

        // 2. Rejects free-text unit and invalid condition (must be dropdown values only)
        $responseInvalidDropdowns = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Pine Wood Offcuts',
            'description' => 'Clean untreated pine wood planks',
            'quantity' => 10,
            'unit' => 'custom_pallets',
            'condition' => 'Unused',
            'image_1' => UploadedFile::fake()->image('p1.jpg', 400, 400),
        ]);
        $responseInvalidDropdowns->assertStatus(422);
        $responseInvalidDropdowns->assertJsonValidationErrors(['unit', 'condition']);

        // 3. Exceeding 3 photos limit
        $excessPhotos = [
            UploadedFile::fake()->image('p1.jpg', 400, 400),
            UploadedFile::fake()->image('p2.jpg', 400, 400),
            UploadedFile::fake()->image('p3.jpg', 400, 400),
            UploadedFile::fake()->image('p4.jpg', 400, 400),
        ];

        $responseMax = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Pine Wood Offcuts',
            'description' => 'Clean untreated pine wood planks',
            'quantity' => 10,
            'unit' => 'kg',
            'condition' => 'Used',
            'images' => $excessPhotos,
        ]);

        $responseMax->assertStatus(422);
        $responseMax->assertJsonValidationErrors(['images']);
    }

    public function test_ai_analyze_sends_actual_uploaded_images_and_condition_to_gemini(): void
    {
        $this->fakeGeminiSuccess();
        $business = $this->createApprovedBusiness();

        $img1 = UploadedFile::fake()->image('wood1.jpg', 500, 400);
        $img2 = UploadedFile::fake()->image('wood2.png', 400, 500);
        $img3 = UploadedFile::fake()->image('wood3.jpg', 450, 450);

        $expectedBase64_1 = base64_encode((string) file_get_contents($img1->getRealPath()));
        $expectedBase64_2 = base64_encode((string) file_get_contents($img2->getRealPath()));
        $expectedBase64_3 = base64_encode((string) file_get_contents($img3->getRealPath()));

        $response = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Solid Oak Flooring Planks',
            'description' => 'Reclaimed solid oak tongue-and-groove flooring planks, denailed and kiln dried.',
            'quantity' => 45,
            'unit' => 'm²',
            'condition' => 'Damaged',
            'image_1' => $img1,
            'image_2' => $img2,
            'image_3' => $img3,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'analysis' => [
                'suggested_category',
                'suggested_reusability',
                'classification_label',
                'reason',
                'recommended_actions',
                'images_analyzed',
            ],
            'analysis_token',
            'saved_images',
            'saved_image_slots',
            'item_data' => [
                'name',
                'description',
                'quantity',
                'unit',
                'condition',
            ],
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertSame(3, $data['analysis']['images_analyzed']);
        $this->assertCount(3, $data['saved_images']);
        $this->assertSame('m²', $data['item_data']['unit']);
        $this->assertSame('Damaged', $data['item_data']['condition']);

        // Assert the actual uploaded image binaries and condition were sent to Gemini (each image once, never URLs)
        Http::assertSent(function (HttpClientRequest $request) use ($expectedBase64_1, $expectedBase64_2, $expectedBase64_3): bool {
            $parts = $request->data()['contents'][0]['parts'] ?? [];
            $textPart = '';
            $inlineDatas = [];

            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $textPart .= $part['text'];
                }
                if (isset($part['inline_data'])) {
                    $inlineDatas[] = $part['inline_data'];
                }
            }

            if (count($inlineDatas) !== 3) {
                return false;
            }

            $sentBase64s = array_column($inlineDatas, 'data');

            return str_contains($textPart, 'Material Condition: Damaged')
                && str_contains($textPart, 'Quantity: 45 m²')
                && str_contains($textPart, 'Solid Oak Flooring Planks')
                && in_array($expectedBase64_1, $sentBase64s, true)
                && in_array($expectedBase64_2, $sentBase64s, true)
                && in_array($expectedBase64_3, $sentBase64s, true)
                && count(array_unique($sentBase64s)) === 3;
        });
    }

    public function test_ai_analyze_returns_clear_error_when_gemini_fails_and_does_not_fake_success(): void
    {
        config()->set('services.gemini.api_key', 'test-gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'Service unavailable'],
            ], 503),
        ]);

        $business = $this->createApprovedBusiness();

        $response = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Steel Sheet Offcuts',
            'description' => 'Clean mild steel sheet metal offcuts.',
            'quantity' => 50,
            'unit' => 'kg',
            'condition' => 'Used',
            'image_1' => UploadedFile::fake()->image('steel.jpg', 500, 400),
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertNotEmpty($response->json('message'));
        $this->assertEmpty(Storage::disk('public')->allFiles('inventory-images'));
    }

    public function test_add_inventory_saves_to_inventory_only_without_auto_publishing_to_marketplace(): void
    {
        $this->fakeGeminiSuccess([
            'suggested_category' => 'Wooden Logistics Pallets',
            'suggested_reusability' => 'reusable',
        ]);

        $business = $this->createApprovedBusiness('Timber Solutions Bhd');
        $viewer = $this->createApprovedBusiness('Buyer Corp');

        $analysisResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Standard Heavy-Duty Wooden Pallets',
            'description' => 'Heavy duty standard euro wooden pallets in good structural condition.',
            'quantity' => 80,
            'unit' => 'pcs',
            'condition' => 'Used',
            'image_1' => UploadedFile::fake()->image('pallet1.jpg', 600, 400),
        ]);
        $analysisResponse->assertOk();

        // Business confirms AI suggestion and clicks "Add to Inventory"
        $storeResponse = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Standard Heavy-Duty Wooden Pallets',
            'description' => 'Heavy duty standard euro wooden pallets in good structural condition.',
            'quantity' => 80,
            'unit' => 'pcs',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'category' => 'Wooden Logistics Pallets',
            'redirect_to' => 'inventory',
            'publish_to_marketplace' => 0,
            'analysis_token' => $analysisResponse->json('analysis_token'),
            'saved_image_slots' => json_encode($analysisResponse->json('saved_image_slots')),
        ]);

        // Redirects back to Inventory with simple success message
        $storeResponse->assertRedirect(route('business.inventory'));
        $storeResponse->assertSessionHas('status');

        $item = InventoryItem::where('name', 'Standard Heavy-Duty Wooden Pallets')->firstOrFail();
        $this->assertFalse(Schema::hasColumn('item', 'inventory_item_id'));
        $this->assertSame('reusable', $item->reusability);
        $this->assertSame('pcs', $item->unit);
        $this->assertSame('Used', $item->condition);
        $this->assertNull($item->price);
        $this->assertSame('in_stock', $item->getRawOriginal('status'));
        $this->assertFalse($item->isPublishedToMarketplace());

        // MUST NOT be automatically published to Marketplace
        $this->flushSession();
        $this->actingAs($viewer)
            ->get(route('business.marketplace'))
            ->assertOk()
            ->assertViewHas('listings', fn ($listings): bool => ! $listings->contains('id', $item->id))
            ->assertDontSee('Standard Heavy-Duty Wooden Pallets');

        // Inventory page shows Update Details and Publish actions for this unpublished reusable item
        $this->actingAs($business)
            ->get(route('business.inventory'))
            ->assertOk()
            ->assertSee('Standard Heavy-Duty Wooden Pallets')
            ->assertSee('Update Details')
            ->assertSee('Publish');

        // Step 4: Cannot publish Sell item with missing/zero price before setting Price per Unit (RM) in Update Item Details
        $this->actingAs($business)
            ->post(route('business.inventory.publish', $item))
            ->assertSessionHasErrors('price');

        // Open Update Inventory Details page, update quantity, Sell listing type, Price per Unit (RM) > 0, and Pickup Address
        $this->actingAs($business)
            ->get(route('business.inventory.edit', $item))
            ->assertOk()
            ->assertSee('Update Inventory Details')
            ->assertSee('Price per Unit (RM)')
            ->assertSee('Collection / Pickup Address');

        $this->actingAs($business)
            ->put(route('business.inventory.update', $item), [
                'name' => 'Standard Heavy-Duty Wooden Pallets',
                'description' => 'Updated pallet specifications.',
                'category' => 'Wooden Logistics Pallets',
                'quantity' => 75,
                'unit' => 'pcs',
                'condition' => 'Used',
                'reusability' => 'reusable',
                'listing_type' => 'buy',
                'price' => 18.50,
                'pickup_address' => 'Lot 9, Jalan Industri 3, 40200 Shah Alam, Selangor',
                'redirect_to' => 'edit',
            ])
            ->assertRedirect(route('business.inventory.edit', $item))
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertEquals(75.0, (float) $item->quantity);
        $this->assertEquals(18.50, (float) $item->price);
        $this->assertSame('Lot 9, Jalan Industri 3, 40200 Shah Alam, Selangor', $item->pickup_address);
        // Editing alone must NOT auto-publish
        $this->assertFalse($item->isPublishedToMarketplace());

        // Step 5: Separate explicit Publish action publishes to Marketplace
        $publishResponse = $this->actingAs($business)
            ->post(route('business.inventory.publish', $item));
        $publishResponse->assertRedirect(route('business.marketplace'));

        $item->refresh();
        $this->assertTrue($item->isPublishedToMarketplace());

        // Now visible on public Marketplace
        $this->actingAs($viewer)
            ->get(route('business.marketplace'))
            ->assertOk()
            ->assertSee('Standard Heavy-Duty Wooden Pallets');
    }

    public function test_edit_details_and_publish_enforce_sell_price_free_zero_and_block_non_reusable(): void
    {
        $business = $this->createApprovedBusiness('Publish Guardrails Bhd');

        // Create unpublished reusable item (no price required)
        $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Clean HDPE Drums',
            'description' => 'Reusable 200L blue plastic drums.',
            'quantity' => 20,
            'unit' => 'pcs',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'category' => 'Plastic Containers',
            'image_1' => UploadedFile::fake()->image('drums.jpg', 400, 400),
        ])->assertSessionHasNoErrors();

        $reusableItem = InventoryItem::where('name', 'Clean HDPE Drums')->firstOrFail();
        $this->assertNull($reusableItem->price);

        // Sell with RM 0.00 is rejected on Edit Details and on Marketplace publish
        $this->actingAs($business)->put(route('business.inventory.update', $reusableItem), [
            'name' => 'Clean HDPE Drums',
            'category' => 'Plastic Containers',
            'quantity' => 20,
            'unit' => 'pcs',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 0,
        ])->assertSessionHasErrors('price');

        $this->actingAs($business)->post(route('business.marketplace.store'), [
            'inventory_item_id' => $reusableItem->id,
            'title' => 'Clean HDPE Drums',
            'category' => 'Plastic Containers',
            'quantity' => 20,
            'unit' => 'pcs',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 0,
        ])->assertSessionHasErrors('price');

        // Configuring as Free enforces RM 0.00 and allows publishing without price > 0
        $this->actingAs($business)->put(route('business.inventory.update', $reusableItem), [
            'name' => 'Clean HDPE Drums',
            'category' => 'Plastic Containers',
            'quantity' => 20,
            'unit' => 'pcs',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'listing_type' => 'free',
        ])->assertSessionHasNoErrors();

        $reusableItem->refresh();
        $this->assertTrue((bool) $reusableItem->is_free);
        $this->assertEquals(0.00, (float) $reusableItem->price);

        $this->actingAs($business)
            ->post(route('business.inventory.publish', $reusableItem))
            ->assertRedirect(route('business.marketplace'));

        $this->assertTrue($reusableItem->fresh()->isPublishedToMarketplace());

        // Non-Reusable item cannot be published via publish endpoint
        $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Oily Metal Swarf',
            'description' => 'Contaminated CNC shavings.',
            'quantity' => 50,
            'unit' => 'kg',
            'condition' => 'Damaged',
            'reusability' => 'not_reusable',
            'category' => 'Metal Scrap',
            'image_1' => UploadedFile::fake()->image('swarf.jpg', 400, 400),
        ])->assertSessionHasNoErrors();

        $scrapItem = InventoryItem::where('name', 'Oily Metal Swarf')->firstOrFail();

        $this->actingAs($business)
            ->post(route('business.inventory.publish', $scrapItem), ['listing_type' => 'free'])
            ->assertSessionHasErrors('reusability');

        $this->assertFalse($scrapItem->fresh()->isPublishedToMarketplace());
    }

    public function test_all_fourteen_dropdown_units_and_twenty_categories_are_accepted(): void
    {
        $business = $this->createApprovedBusiness('Dropdown Validation Bhd');

        $this->assertSame(['New', 'Damaged', 'Used'], Item::ALLOWED_CONDITIONS);
        $this->assertSame(
            ['kg', 'g', 't', 'pcs', 'Unit', 'L', 'ml', 'm', 'm²', 'm³', 'Box', 'Bundle', 'Bag', 'Roll'],
            array_keys(Item::ALLOWED_UNITS)
        );
        $this->assertSame([
            'Food Waste',
            'Paper & Cardboard',
            'Plastic',
            'Metal',
            'Glass',
            'Wood',
            'Textile & Fabric',
            'Rubber',
            'Electronic Waste (E-Waste)',
            'Electrical Equipment',
            'Construction & Demolition Waste',
            'Garden & Green Waste',
            'Chemical Waste',
            'Used Oil & Grease',
            'Batteries',
            'Packaging Materials',
            'Ceramic & Porcelain',
            'Leather',
            'Mixed Waste',
            'Other',
        ], Item::ALLOWED_CATEGORIES);

        foreach (['kg', 'm²', 'm³', 'Bag', 'Box', 't', 'L', 'ml', 'Bundle', 'Roll', 'Unit'] as $unit) {
            $response = $this->actingAs($business)->post(route('business.inventory.store'), [
                'name' => "Material {$unit}",
                'description' => 'Standardized measurement test material.',
                'quantity' => 10,
                'unit' => $unit,
                'condition' => 'Damaged',
                'reusability' => 'reusable',
                'category' => 'Plastic',
                'image_1' => UploadedFile::fake()->image("img_{$unit}.jpg", 300, 300),
            ]);
            $response->assertRedirect(route('business.inventory'));
            $response->assertSessionHasNoErrors();
            $this->assertDatabaseHas('item', [
                'name' => "Material {$unit}",
                'unit' => $unit,
                'category' => 'Plastic',
                'condition' => 'Damaged',
            ]);
        }
    }

    public function test_reusable_item_can_be_added_directly_to_inventory_and_proceed_to_listing_without_ai_analysis(): void
    {
        $business = $this->createApprovedBusiness('Direct Add Test Sdn Bhd');

        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Direct Reusable Timber Lot',
            'description' => 'Adding reusable item directly without AI analysis.',
            'quantity' => 50,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'category' => 'Furniture & wood products',
            'condition' => 'Used',
            'redirect_to' => 'listing',
            'images' => [UploadedFile::fake()->image('pallet1.jpg', 600, 400)],
        ]);

        $item = InventoryItem::where('name', 'Direct Reusable Timber Lot')->firstOrFail();
        $response->assertRedirect(route('business.inventory.edit', $item));
        $this->assertSame('reusable', $item->reusability);
        $this->assertNull($item->ai_analysis);
    }

    public function test_non_reusable_ai_analysis_provides_step_by_step_guidance_and_suitable_disposal_locations(): void
    {
        $business = $this->createApprovedBusiness('Scrap Metal Processing Sdn Bhd');

        $response = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Damaged Copper Transformers Scrap',
            'description' => 'Burnt out industrial transformer windings with contaminated insulation.',
            'quantity' => 120,
            'unit' => 'kg',
            'condition' => 'Damaged',
            'category' => 'Metals & machinery',
            'reusability' => 'not_reusable',
            'image_1' => UploadedFile::fake()->image('scrap_copper.jpg', 600, 400),
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'analysis' => [
                'strategy',
                'disposal_locations',
            ],
            'disposal_locations',
        ]);
        $this->assertNotEmpty($response->json('disposal_locations'));
    }

    public function test_non_reusable_item_cannot_be_published_to_marketplace_even_with_manual_request(): void
    {
        $this->fakeGeminiSuccess([
            'suggested_category' => 'Contaminated Industrial Textiles',
            'suggested_reusability' => 'not_reusable',
            'classification_label' => 'Non-Reusable',
        ]);

        $business = $this->createApprovedBusiness('Scrap Control Bhd');

        $analysisResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Contaminated Oily Rags',
            'description' => 'Used industrial rags soaked in machine oil.',
            'quantity' => 30,
            'unit' => 'kg',
            'condition' => 'Damaged',
            'image_1' => UploadedFile::fake()->image('scrap1.jpg', 600, 400),
        ]);
        $analysisResponse->assertOk();

        // Attempt to force publishing a Non-Reusable item to the Marketplace
        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Contaminated Oily Rags',
            'description' => 'Used industrial rags soaked in machine oil.',
            'quantity' => 30,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'category' => 'Contaminated Industrial Textiles',
            'listing_type' => 'buy',
            'price' => 5.00,
            'condition' => 'Damaged',
            'redirect_to' => 'marketplace',
            'publish_to_marketplace' => 1,
            'analysis_token' => $analysisResponse->json('analysis_token'),
            'saved_images' => json_encode($analysisResponse->json('saved_images')),
        ]);

        $response->assertSessionHasErrors('reusability');
        $this->assertDatabaseMissing('item', [
            'name' => 'Contaminated Oily Rags',
        ]);
    }

    public function test_store_non_reusable_item_stays_in_inventory_and_accessible_in_ecocycle_helper(): void
    {
        $business = $this->createApprovedBusiness('Metal Works Enterprise');

        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Mixed CNC Aluminium Swarf and Shavings',
            'description' => 'Fine machine cutting swarf contaminated with coolant fluid. Not reusable in current state.',
            'quantity' => 350,
            'unit' => 'kg',
            'condition' => 'Damaged',
            'reusability' => 'not_reusable',
            'category' => 'Metal & fabrication',
            'redirect_to' => 'inventory',
            'publish_to_marketplace' => 0,
            'image_1' => UploadedFile::fake()->image('metal_shavings.jpg', 600, 400),
        ]);

        $item = InventoryItem::where('name', 'Mixed CNC Aluminium Swarf and Shavings')->first();
        $this->assertNotNull($item);
        $this->assertEquals('not_reusable', $item->reusability);
        $this->assertFalse($item->isPublishedToMarketplace());

        // Redirects back to Inventory
        $response->assertRedirect(route('business.inventory'));

        // Does NOT appear in normal reusable marketplace listings
        $marketplaceResponse = $this->actingAs($business)->get(route('business.marketplace'));
        $marketplaceResponse->assertOk();
        $marketplaceResponse->assertViewHas('listings', function ($listings) use ($item) {
            return ! $listings->contains('id', $item->id);
        });

        // Accessible in EcoCycle Helper
        $helperResponse = $this->actingAs($business)->get(route('business.helper', ['item_id' => $item->id]));
        $helperResponse->assertOk();
        $helperResponse->assertSee('Mixed CNC Aluminium Swarf and Shavings');
        $helperResponse->assertSee('Recycling');
    }

    public function test_store_accepts_pre_saved_images_from_ai_analysis(): void
    {
        $business = $this->createApprovedBusiness();

        Storage::disk('public')->put('inventory-images/pre_analyzed_1.jpg', 'fake-image-binary');
        $savedImages = ['/inventory-images/pre_analyzed_1.jpg'];

        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'High Purity HDPE Granules',
            'description' => 'Recycled high-density polyethylene pellets.',
            'quantity' => 200,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'category' => 'Plastics & rubber',
            'condition' => 'New',
            'redirect_to' => 'inventory',
            'publish_to_marketplace' => 0,
            'saved_images' => json_encode($savedImages),
        ]);

        $response->assertRedirect(route('business.inventory'));

        $this->assertDatabaseHas('item', [
            'name' => 'High Purity HDPE Granules',
            'reusability' => 'reusable',
            'condition' => 'New',
            'status' => 'in_stock',
            'image_path' => '/inventory-images/pre_analyzed_1.jpg',
        ]);
    }

    public function test_single_image_upload_after_ai_analysis_does_not_duplicate_into_image_2(): void
    {
        $this->fakeGeminiSuccess(['suggested_category' => 'Structural Steel']);
        $business = $this->createApprovedBusiness('Single Slot Sdn Bhd');

        $photo = UploadedFile::fake()->image('single_photo.jpg', 500, 500);

        $analysisResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Clean Steel Rods',
            'description' => 'New mild steel rods stored indoors.',
            'quantity' => 40,
            'unit' => 'kg',
            'condition' => 'New',
            'image_1' => $photo,
        ]);
        $analysisResponse->assertOk();

        $savedImages = $analysisResponse->json('saved_images');
        $savedSlots = $analysisResponse->json('saved_image_slots');

        $this->assertCount(1, $savedImages);
        $this->assertNotNull($savedSlots[0]);
        $this->assertNull($savedSlots[1]);
        $this->assertNull($savedSlots[2]);

        // Even if the client ALSO submits images[] alongside saved_images/saved_image_slots,
        // the backend must NOT duplicate Image 1 into Image 2.
        $storeResponse = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Clean Steel Rods',
            'description' => 'New mild steel rods stored indoors.',
            'quantity' => 40,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'category' => 'Structural Steel',
            'condition' => 'New',
            'redirect_to' => 'inventory',
            'publish_to_marketplace' => 0,
            'saved_images' => json_encode($savedImages),
            'saved_image_slots' => json_encode($savedSlots),
            'images' => [UploadedFile::fake()->image('single_photo.jpg', 500, 500)],
        ]);

        $storeResponse->assertRedirect(route('business.inventory'));

        $item = InventoryItem::where('name', 'Clean Steel Rods')->firstOrFail();
        $this->assertSame($savedSlots[0], $item->image_1);
        $this->assertNull($item->image_2);
        $this->assertNull($item->image_3);
        $this->assertCount(1, $item->getAllImages());
    }

    public function test_independent_image_slots_can_be_uploaded_replaced_and_removed_without_shifting(): void
    {
        $business = $this->createApprovedBusiness('Slot Independence Bhd');

        $img1 = UploadedFile::fake()->image('slot1.jpg', 400, 400);
        $img3 = UploadedFile::fake()->image('slot3.jpg', 400, 400);

        $createResponse = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Reclaimed Glass Sheets',
            'description' => 'Tempered clear glass panels.',
            'quantity' => 15,
            'unit' => 'm²',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'category' => 'Tempered Glass Panels',
            'image_1' => $img1,
            'image_3' => $img3,
        ]);
        $createResponse->assertSessionHasNoErrors();

        $item = InventoryItem::where('name', 'Reclaimed Glass Sheets')->firstOrFail();
        $originalImage1 = $item->image_1;
        $originalImage3 = $item->image_3;

        $this->assertNotNull($originalImage1);
        $this->assertNull($item->image_2);
        $this->assertNotNull($originalImage3);

        // Remove Image 1 and replace Image 3 independently; Image 2 must remain null and Image 3 must stay in slot 3
        $newImg3 = UploadedFile::fake()->image('slot3_new.jpg', 400, 400);
        $updateResponse = $this->actingAs($business)->put(route('business.inventory.update', $item), [
            'name' => 'Reclaimed Glass Sheets',
            'description' => 'Tempered clear glass panels.',
            'category' => 'Tempered Glass Panels',
            'unit' => 'm²',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'remove_image_1' => 1,
            'image_3' => $newImg3,
        ]);
        $updateResponse->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertNull($item->image_1);
        $this->assertNull($item->image_2);
        $this->assertNotNull($item->image_3);
        $this->assertNotSame($originalImage3, $item->image_3);
        $this->assertSame($item->image_3, $item->image_path);
        $this->assertCount(1, $item->getAllImages());
    }

    public function test_dynamic_ai_category_detection_preserves_gemini_category_without_hardcoded_override(): void
    {
        $this->fakeGeminiSuccess([
            'suggested_category' => 'Borosilicate Laboratory Glass',
            'category_confidence' => 'high',
            'suggested_reusability' => 'reusable',
            'classification_label' => 'Reusable',
            'reason' => 'Intact heat-resistant borosilicate glass beakers suitable for laboratory reuse.',
        ]);

        $business = $this->createApprovedBusiness('Lab Glass Sdn Bhd');

        $analysisResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => '500ml Lab Beakers',
            'description' => 'Clean heat-resistant borosilicate laboratory glassware in intact condition.',
            'quantity' => 25,
            'unit' => 'pcs',
            'condition' => 'New',
            'image_1' => UploadedFile::fake()->image('beakers.jpg', 500, 500),
        ]);

        $analysisResponse->assertOk();
        $analysisResponse->assertJsonPath('analysis.suggested_category', 'Borosilicate Laboratory Glass');
        $analysisResponse->assertJsonPath('analysis.category_confidence', 'high');
        $analysisResponse->assertJsonPath('analysis.needs_category_review', false);

        // Confirm and save the dynamic AI category verbatim into Inventory
        $storeResponse = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => '500ml Lab Beakers',
            'description' => 'Clean heat-resistant borosilicate laboratory glassware in intact condition.',
            'quantity' => 25,
            'unit' => 'pcs',
            'condition' => 'New',
            'reusability' => 'reusable',
            'category' => $analysisResponse->json('analysis.suggested_category'),
            'redirect_to' => 'inventory',
            'publish_to_marketplace' => 0,
            'saved_image_slots' => json_encode($analysisResponse->json('saved_image_slots')),
        ]);

        $storeResponse->assertRedirect(route('business.inventory'));
        $this->assertDatabaseHas('item', [
            'name' => '500ml Lab Beakers',
            'category' => 'Borosilicate Laboratory Glass',
            'unit' => 'pcs',
            'condition' => 'New',
            'status' => 'in_stock',
        ]);
    }

    public function test_fallback_category_detection_supports_malay_food_waste_and_flags_low_confidence(): void
    {
        config()->set('services.gemini.api_key', '');

        $business = $this->createApprovedBusiness('Catering Circular Bhd');

        // 1. Malay food waste should be accurately identified as Organic Food Waste (Non-Reusable)
        $foodResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Sisa Makanan',
            'description' => 'Lebihan sayur dan sisa dapur harian dari kantin kilang.',
            'quantity' => 50,
            'unit' => 'kg',
            'condition' => 'Damaged',
            'image_1' => UploadedFile::fake()->image('food_waste.jpg', 400, 400),
        ]);

        $foodResponse->assertOk();
        $foodResponse->assertJsonPath('analysis.suggested_category', 'Organic Food Waste');
        $foodResponse->assertJsonPath('analysis.suggested_reusability', 'not_reusable');

        // 2. Unidentifiable material should NOT silently default to "Other" — it flags needs_category_review
        $unknownResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Batch XYZ-999',
            'description' => 'Unlabeled item from storage room B.',
            'quantity' => 5,
            'unit' => 'pcs',
            'condition' => 'Used',
            'image_1' => UploadedFile::fake()->image('mystery.jpg', 400, 400),
        ]);

        $unknownResponse->assertOk();
        $unknownResponse->assertJsonPath('analysis.suggested_category', '');
        $unknownResponse->assertJsonPath('analysis.category_confidence', 'low');
        $unknownResponse->assertJsonPath('analysis.needs_category_review', true);
    }
}
