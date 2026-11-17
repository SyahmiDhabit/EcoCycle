<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\MarketplaceListing;
use App\Models\User;
use App\Services\EcoCycleHelperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    public function test_ai_analyze_endpoint_requires_approved_business(): void
    {
        $guestResponse = $this->postJson(route('business.inventory.ai-analyze'), []);
        $guestResponse->assertUnauthorized();

        $pendingBusiness = $this->createPendingBusiness();
        $pendingResponse = $this->actingAs($pendingBusiness)->postJson(route('business.inventory.ai-analyze'), []);
        $pendingResponse->assertForbidden();
    }

    public function test_ai_analyze_validates_required_fields_and_photo_limits(): void
    {
        $business = $this->createApprovedBusiness();

        // 1. Missing all fields
        $response = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'description', 'quantity', 'unit', 'images']);

        // 2. Exceeding 3 photos limit
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
            'images' => $excessPhotos,
        ]);

        $responseMax->assertStatus(422);
        $responseMax->assertJsonValidationErrors(['images']);
    }

    public function test_ai_analyze_returns_gemini_analysis_and_saved_images(): void
    {
        $business = $this->createApprovedBusiness();

        $images = [
            UploadedFile::fake()->image('wood1.jpg', 500, 500),
            UploadedFile::fake()->image('wood2.jpg', 500, 500),
        ];

        $response = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Solid Oak Flooring Planks',
            'description' => 'Reclaimed solid oak tongue-and-groove flooring planks, denailed and kiln dried. Excellent condition for furniture making.',
            'quantity' => 45,
            'unit' => 'sqm',
            'images' => $images,
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
            ],
            'analysis_token',
            'saved_images',
            'item_data' => [
                'name',
                'description',
                'quantity',
                'unit',
            ],
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['analysis_token']);
        $this->assertNotEmpty($data['saved_images']);
        $this->assertCount(2, $data['saved_images']);
        $this->assertContains($data['analysis']['suggested_reusability'], ['reusable', 'not_reusable']);
    }

    public function test_store_reusable_item_publishes_to_marketplace_and_redirects(): void
    {
        $business = $this->createApprovedBusiness('Timber Solutions Bhd');

        $images = [
            UploadedFile::fake()->image('pallet1.jpg', 600, 400),
        ];

        $analysisResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Standard Heavy-Duty Wooden Pallets',
            'description' => 'Heavy duty standard euro wooden pallets in good structural condition.',
            'quantity' => 80,
            'unit' => 'pallets',
            'images' => $images,
        ]);
        $analysisResponse->assertOk();
        $analysisToken = $analysisResponse->json('analysis_token');
        $savedImages = $analysisResponse->json('saved_images');

        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Standard Heavy-Duty Wooden Pallets',
            'description' => 'Heavy duty standard euro wooden pallets in good structural condition.',
            'quantity' => 80,
            'unit' => 'pallets',
            'reusability' => 'reusable',
            'category' => 'Furniture & wood products',
            'listing_type' => 'buy',
            'price' => 18.50,
            'condition' => 'Used',
            'available_quantity' => 80,
            'pickup_location' => 'Shah Alam, Selangor',
            'redirect_to' => 'marketplace',
            'publish_to_marketplace' => 1,
            'analysis_token' => $analysisToken,
            'saved_images' => json_encode($savedImages),
        ]);

        $response->assertRedirect(route('business.marketplace'));
        $response->assertSessionHas('status');

        // Verify stored in singular item table as reusable
        $this->assertDatabaseHas('item', [
            'business_id' => $business->id,
            'name' => 'Standard Heavy-Duty Wooden Pallets',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 18.50,
            'status' => 'active',
        ]);

        // Verify it is visible in marketplace
        $marketplaceResponse = $this->actingAs($business)->get(route('business.marketplace'));
        $marketplaceResponse->assertOk();
        $marketplaceResponse->assertSee('Standard Heavy-Duty Wooden Pallets');
    }

    public function test_unanalysed_item_cannot_be_published_to_marketplace(): void
    {
        $business = $this->createApprovedBusiness('Unanalysed Test Sdn Bhd');

        $images = [
            UploadedFile::fake()->image('pallet1.jpg', 600, 400),
        ];

        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Unanalysed Timber Lot',
            'description' => 'Attempting to publish without running material analysis.',
            'quantity' => 50,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'category' => 'Furniture & wood products',
            'listing_type' => 'buy',
            'price' => 20.00,
            'condition' => 'Used',
            'available_quantity' => 50,
            'pickup_location' => 'Shah Alam, Selangor',
            'redirect_to' => 'marketplace',
            'publish_to_marketplace' => 1,
            'images' => $images,
        ]);

        $response->assertSessionHasErrors('analysis_token');
        $this->assertDatabaseMissing('item', [
            'name' => 'Unanalysed Timber Lot',
        ]);
    }

    public function test_non_reusable_item_cannot_be_published_to_marketplace_even_with_manual_request(): void
    {
        $business = $this->createApprovedBusiness('Scrap Control Bhd');

        $images = [
            UploadedFile::fake()->image('scrap1.jpg', 600, 400),
        ];

        $analysisResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Contaminated Oily Rags',
            'description' => 'Used industrial rags soaked in machine oil.',
            'quantity' => 30,
            'unit' => 'kg',
            'images' => $images,
        ]);
        $analysisResponse->assertOk();
        $analysisToken = $analysisResponse->json('analysis_token');
        $savedImages = $analysisResponse->json('saved_images');

        // Attempt to force publishing a Non-Reusable item to the Marketplace
        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Contaminated Oily Rags',
            'description' => 'Used industrial rags soaked in machine oil.',
            'quantity' => 30,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'category' => 'Textiles & apparel',
            'listing_type' => 'buy',
            'price' => 5.00,
            'condition' => 'Used',
            'redirect_to' => 'marketplace',
            'publish_to_marketplace' => 1,
            'analysis_token' => $analysisToken,
            'saved_images' => json_encode($savedImages),
        ]);

        $response->assertSessionHasErrors('reusability');
        $this->assertDatabaseMissing('item', [
            'name' => 'Contaminated Oily Rags',
        ]);
    }

    public function test_store_non_reusable_item_forwards_to_ecocycle_helper(): void
    {
        $business = $this->createApprovedBusiness('Metal Works Enterprise');

        $images = [
            UploadedFile::fake()->image('metal_shavings.jpg', 600, 400),
        ];

        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Mixed CNC Aluminium Swarf and Shavings',
            'description' => 'Fine machine cutting swarf contaminated with coolant fluid. Not reusable in current state.',
            'quantity' => 350,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'category' => 'Metal & fabrication',
            'redirect_to' => 'helper',
            'publish_to_marketplace' => 0,
            'images' => $images,
        ]);

        // Verify item created
        $item = InventoryItem::where('name', 'Mixed CNC Aluminium Swarf and Shavings')->first();
        $this->assertNotNull($item);
        $this->assertEquals('not_reusable', $item->reusability);

        // Verify redirection to EcoCycle Helper with item_id
        $response->assertRedirect(route('business.helper', ['item_id' => $item->id]));

        // Verify it does NOT appear in normal reusable marketplace listings
        $marketplaceResponse = $this->actingAs($business)->get(route('business.marketplace'));
        $marketplaceResponse->assertOk();
        $marketplaceResponse->assertViewHas('listings', function ($listings) use ($item) {
            return ! $listings->contains('id', $item->id);
        });

        // Verify it is accessible in EcoCycle Helper
        $helperResponse = $this->actingAs($business)->get(route('business.helper', ['item_id' => $item->id]));
        $helperResponse->assertOk();
        $helperResponse->assertSee('Mixed CNC Aluminium Swarf and Shavings');
        $helperResponse->assertSee('Recycling');
    }

    public function test_store_accepts_pre_saved_images_from_ai_analysis(): void
    {
        $business = $this->createApprovedBusiness();

        // Simulate photos saved during aiAnalyze and valid analysis token
        Storage::disk('public')->put('inventory-images/pre_analyzed_1.jpg', 'fake-image-binary');
        $savedImages = ['/inventory-images/pre_analyzed_1.jpg'];
        $analysisToken = \App\Http\Controllers\InventoryController::generateAnalysisToken(
            $business->id,
            'High Purity HDPE Granules',
            200.0
        );

        $response = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'High Purity HDPE Granules',
            'description' => 'Recycled high-density polyethylene pellets.',
            'quantity' => 200,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'category' => 'Plastics & rubber',
            'listing_type' => 'free',
            'condition' => 'Unused',
            'redirect_to' => 'marketplace',
            'publish_to_marketplace' => 1,
            'analysis_token' => $analysisToken,
            'saved_images' => json_encode($savedImages),
        ]);

        $response->assertRedirect(route('business.marketplace'));

        $this->assertDatabaseHas('item', [
            'name' => 'High Purity HDPE Granules',
            'reusability' => 'reusable',
            'image_path' => '/inventory-images/pre_analyzed_1.jpg',
        ]);
    }

    public function test_single_image_upload_after_ai_analysis_does_not_duplicate_into_image_2(): void
    {
        $business = $this->createApprovedBusiness('Single Slot Sdn Bhd');

        $photo = UploadedFile::fake()->image('single_photo.jpg', 500, 500);

        $analysisResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => 'Clean Steel Rods',
            'description' => 'Unused mild steel rods stored indoors.',
            'quantity' => 40,
            'unit' => 'kg',
            'image_1' => $photo,
        ]);
        $analysisResponse->assertOk();

        $savedImages = $analysisResponse->json('saved_images');
        $savedSlots = $analysisResponse->json('saved_image_slots');
        $analysisToken = $analysisResponse->json('analysis_token');

        $this->assertCount(1, $savedImages);
        $this->assertNotNull($savedSlots[0]);
        $this->assertNull($savedSlots[1]);
        $this->assertNull($savedSlots[2]);

        // Even if the client ALSO submits images[] alongside saved_images/saved_image_slots,
        // the backend must NOT duplicate Image 1 into Image 2.
        $storeResponse = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Clean Steel Rods',
            'description' => 'Unused mild steel rods stored indoors.',
            'quantity' => 40,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'category' => 'Structural Steel',
            'listing_type' => 'buy',
            'price' => 12.00,
            'condition' => 'New',
            'available_quantity' => 40,
            'pickup_location' => 'Shah Alam, Selangor',
            'redirect_to' => 'marketplace',
            'publish_to_marketplace' => 1,
            'analysis_token' => $analysisToken,
            'saved_images' => json_encode($savedImages),
            'saved_image_slots' => json_encode($savedSlots),
            'images' => [UploadedFile::fake()->image('single_photo.jpg', 500, 500)],
        ]);

        $storeResponse->assertRedirect(route('business.marketplace'));

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
            'unit' => 'panels',
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
            'unit' => 'panels',
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
        config()->set('services.gemini.api_key', 'test-gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'suggested_category' => 'Borosilicate Laboratory Glass',
                                        'category_confidence' => 'high',
                                        'suggested_reusability' => 'reusable',
                                        'classification_label' => 'Reusable',
                                        'reason' => 'Intact heat-resistant borosilicate glass beakers suitable for laboratory reuse.',
                                        'recommended_actions' => [
                                            'List on Marketplace for educational or lab reuse.',
                                        ],
                                        'recycling_recommendations' => 'Separate from soda-lime glass before recycling.',
                                        'repurposing_ideas' => ['Reuse in industrial testing labs'],
                                        'disposal_method' => 'Send to specialized glass recovery center.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $business = $this->createApprovedBusiness('Lab Glass Sdn Bhd');

        $analysisResponse = $this->actingAs($business)->postJson(route('business.inventory.ai-analyze'), [
            'name' => '500ml Lab Beakers',
            'description' => 'Clean heat-resistant borosilicate laboratory glassware in intact condition.',
            'quantity' => 25,
            'unit' => 'pieces',
            'image_1' => UploadedFile::fake()->image('beakers.jpg', 500, 500),
        ]);

        $analysisResponse->assertOk();
        $analysisResponse->assertJsonPath('analysis.suggested_category', 'Borosilicate Laboratory Glass');
        $analysisResponse->assertJsonPath('analysis.category_confidence', 'high');
        $analysisResponse->assertJsonPath('analysis.needs_category_review', false);

        // Confirm and save the dynamic AI category verbatim
        $storeResponse = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => '500ml Lab Beakers',
            'description' => 'Clean heat-resistant borosilicate laboratory glassware in intact condition.',
            'quantity' => 25,
            'unit' => 'pieces',
            'reusability' => 'reusable',
            'category' => $analysisResponse->json('analysis.suggested_category'),
            'listing_type' => 'buy',
            'price' => 15.00,
            'condition' => 'New',
            'available_quantity' => 25,
            'pickup_location' => 'Shah Alam, Selangor',
            'redirect_to' => 'marketplace',
            'publish_to_marketplace' => 1,
            'analysis_token' => $analysisResponse->json('analysis_token'),
            'saved_image_slots' => json_encode($analysisResponse->json('saved_image_slots')),
        ]);

        $storeResponse->assertRedirect(route('business.marketplace'));
        $this->assertDatabaseHas('item', [
            'name' => '500ml Lab Beakers',
            'category' => 'Borosilicate Laboratory Glass',
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
            'unit' => 'units',
            'image_1' => UploadedFile::fake()->image('mystery.jpg', 400, 400),
        ]);

        $unknownResponse->assertOk();
        $unknownResponse->assertJsonPath('analysis.suggested_category', '');
        $unknownResponse->assertJsonPath('analysis.category_confidence', 'low');
        $unknownResponse->assertJsonPath('analysis.needs_category_review', true);
    }
}
