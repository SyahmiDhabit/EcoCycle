<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceReview;
use App\Models\MaterialExchange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function createApprovedBusiness(string $name = 'Eco Alpha Sdn Bhd'): User
    {
        return User::factory()->create([
            'business_name' => $name,
            'account_status' => 'approved',
            'city' => 'Shah Alam',
            'state' => 'Selangor',
        ]);
    }

    public function test_business_can_create_inventory_with_reusability(): void
    {
        $business = $this->createApprovedBusiness();

        $images = [
            UploadedFile::fake()->image('inv1.jpg', 600, 400),
            UploadedFile::fake()->image('inv2.jpg', 600, 400),
        ];

        $response = $this->actingAs($business)
            ->from(route('business.inventory'))
            ->post(route('business.inventory.store'), [
                'name' => 'Industrial Plastic Pallets',
                'description' => 'Industrial grade heavy duty pallets with reinforced corners.',
                'category' => 'Packaging & paper',
                'quantity' => 150,
                'unit' => 'pcs',
                'condition' => 'Used',
                'reusability' => 'reusable',
                'images' => $images,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('business.inventory'));
        $item = InventoryItem::where('name', 'Industrial Plastic Pallets')->firstOrFail();
        $this->assertSame('reusable', $item->reusability);
        $this->assertSame('Industrial grade heavy duty pallets with reinforced corners.', $item->description);
        $this->assertNotNull($item->image_path);
        $this->assertNotNull($item->image_1);
        $this->assertNotNull($item->image_2);
        $this->assertNull($item->image_3);
        $this->assertCount(2, $item->getAllImages());

        // Test inventory image route returns 200
        $firstFilename = basename($item->image_path);
        $this->get('/inventory-images/'.$firstFilename)->assertOk();

        // Test listing from this inventory item inherits the images automatically!
        $listResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'inventory_item_id' => $item->id,
            'title' => 'Listed Pallets',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'quantity' => 50,
            'unit' => 'pcs',
            'condition' => 'Used',
            'price' => 18.00,
        ]);
        $listResponse->assertRedirect();
        $listing = MarketplaceListing::where('title', 'Listed Pallets')->firstOrFail();
        $this->assertCount(2, $listing->getAllImages());
        $this->assertSame($item->image_path, $listing->image_path);
        $this->assertSame($item->image_1, $listing->image_1);
        $this->assertSame($item->image_2, $listing->image_2);
        $this->assertNull($listing->image_3);
    }

    public function test_business_can_create_marketplace_listing_with_sell_or_free_and_valid_conditions(): void
    {
        $business = $this->createApprovedBusiness();

        // 1. Sell listing with New condition
        $buyResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Treated Timber Offcuts',
            'category' => 'Furniture & wood products',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'condition' => 'New',
            'price' => 45.00,
            'quantity' => 50,
            'unit' => 'kg',
            'location_city' => 'Klang',
            'location_state' => 'Selangor',
        ]);
        $buyResponse->assertRedirect();
        $this->assertDatabaseHas('item', [
            'business_id' => $business->id,
            'title' => 'Treated Timber Offcuts',
            'listing_type' => 'buy',
            'condition' => 'New',
            'reusability' => 'reusable',
            'is_free' => false,
            'price' => 45.00,
        ]);

        // 2. Borrow listing type is rejected (only Sell and Free allowed)
        $borrowResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Heavy Duty Reusable Crates',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'borrow',
            'condition' => 'Used',
            'price' => 10.00,
            'quantity' => 30,
            'unit' => 'Box',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
        ]);
        $borrowResponse->assertSessionHasErrors('listing_type');

        // 3. Invalid condition is rejected (only New, Damaged, Used allowed)
        $invalidCondResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Invalid Condition Crate',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'condition' => 'Unused',
            'price' => 10.00,
            'quantity' => 10,
            'unit' => 'Box',
        ]);
        $invalidCondResponse->assertSessionHasErrors('condition');

        // 4. Free listing with Damaged condition
        $freeResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Clean Shredded Cardboard',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'free',
            'condition' => 'Damaged',
            'price' => 0.00,
            'quantity' => 200,
            'unit' => 'kg',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
        ]);
        $freeResponse->assertRedirect();
        $this->assertDatabaseHas('item', [
            'business_id' => $business->id,
            'title' => 'Clean Shredded Cardboard',
            'listing_type' => 'free',
            'condition' => 'Damaged',
            'is_free' => true,
            'price' => 0.00,
        ]);
    }

    public function test_business_can_upload_one_to_three_images_for_marketplace_listing(): void
    {
        Storage::fake('public');
        $business = $this->createApprovedBusiness();

        $images = [
            UploadedFile::fake()->image('pallet1.jpg', 600, 400),
            UploadedFile::fake()->image('pallet2.jpg', 600, 400),
            UploadedFile::fake()->image('pallet3.jpg', 600, 400),
        ];

        $response = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Grade A Wooden Pallets 120x100',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'condition' => 'Used',
            'price' => 22.50,
            'quantity' => 120,
            'unit' => 'pcs',
            'images' => $images,
        ]);

        $response->assertRedirect();

        $listing = MarketplaceListing::where('title', 'Grade A Wooden Pallets 120x100')->firstOrFail();
        $this->assertNotNull($listing->image_path);
        $this->assertNotNull($listing->image_1);
        $this->assertNotNull($listing->image_2);
        $this->assertNotNull($listing->image_3);
        $this->assertCount(3, $listing->getAllImages());
        $this->assertSame($listing->image_path, $listing->getAllImages()[0]);

        // Test image route returns 200
        $firstFilename = basename($listing->image_path);
        $this->get('/listing-images/'.$firstFilename)->assertOk();
    }

    public function test_enforces_maximum_three_images_for_product_and_inventory(): void
    {
        $business = $this->createApprovedBusiness();

        $fourImages = [
            UploadedFile::fake()->image('img1.jpg', 600, 400),
            UploadedFile::fake()->image('img2.jpg', 600, 400),
            UploadedFile::fake()->image('img3.jpg', 600, 400),
            UploadedFile::fake()->image('img4.jpg', 600, 400),
        ];

        // Exceeding 3 images is rejected for inventory
        $this->actingAs($business)
            ->from(route('business.inventory'))
            ->post(route('business.inventory.store'), [
                'name' => 'Excess Image Inventory Item',
                'category' => 'Metal & fabrication',
                'quantity' => 100,
                'unit' => 'kg',
                'reusability' => 'reusable',
                'images' => $fourImages,
            ])->assertSessionHasErrors('images');

        // Exceeding 3 images is rejected for marketplace
        $this->actingAs($business)
            ->post(route('business.marketplace.store'), [
                'title' => 'Excess Image Listing',
                'category' => 'Metal & fabrication',
                'reusability' => 'reusable',
                'listing_type' => 'buy',
                'quantity' => 20,
                'unit' => 'kg',
                'price' => 50.00,
                'images' => $fourImages,
            ])->assertSessionHasErrors('images');

        $threeImages = array_slice($fourImages, 0, 3);

        // 1. Inventory with 3 images
        $invResponse = $this->actingAs($business)
            ->from(route('business.inventory'))
            ->post(route('business.inventory.store'), [
                'name' => 'Complete 3-Image Inventory Item',
                'category' => 'Metal & fabrication',
                'quantity' => 100,
                'unit' => 'kg',
                'reusability' => 'reusable',
                'images' => $threeImages,
            ]);
        $invResponse->assertRedirect(route('business.inventory'));

        $item = InventoryItem::where('name', 'Complete 3-Image Inventory Item')->firstOrFail();
        $this->assertNotNull($item->image_1);
        $this->assertNotNull($item->image_2);
        $this->assertNotNull($item->image_3);
        $this->assertCount(3, $item->getAllImages());

        // 2. Marketplace listing with 3 images
        $mktResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Complete 3-Image Listing',
            'category' => 'Metal & fabrication',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'quantity' => 20,
            'unit' => 'kg',
            'price' => 50.00,
            'images' => $threeImages,
        ]);
        $mktResponse->assertRedirect();

        $listing = MarketplaceListing::where('title', 'Complete 3-Image Listing')->firstOrFail();
        $this->assertNotNull($listing->image_1);
        $this->assertNotNull($listing->image_2);
        $this->assertNotNull($listing->image_3);
        $this->assertCount(3, $listing->getAllImages());

        // Test delete cleans up stored files
        $firstListingImage = basename($listing->image_1);
        $this->actingAs($business)->delete(route('business.marketplace.destroy', $listing));
        $this->assertFalse(Storage::disk('public')->exists('listing-images/'.$firstListingImage));
    }

    public function test_marketplace_filters_by_listing_type_and_reusability(): void
    {
        $business = $this->createApprovedBusiness('Owner Business');
        $otherBusiness = $this->createApprovedBusiness('Other Business');

        MarketplaceListing::create([
            'business_id' => $business->id,
            'title' => 'Item Reusable Sell',
            'description' => 'Test durable sell item',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'quantity' => 10,
            'unit' => 'pcs',
            'price' => 5,
            'is_free' => false,
            'location_city' => 'Klang',
            'location_state' => 'Selangor',
            'status' => 'active',
        ]);

        MarketplaceListing::create([
            'business_id' => $business->id,
            'title' => 'Item Not Reusable Free',
            'description' => 'Test scrap free item',
            'category' => 'Plastics & rubber',
            'reusability' => 'not_reusable',
            'listing_type' => 'free',
            'quantity' => 10,
            'unit' => 'kg',
            'price' => 0,
            'is_free' => true,
            'location_city' => 'Klang',
            'location_state' => 'Selangor',
            'status' => 'active',
        ]);

        // Filter by buy (Sell) for other business
        $this->actingAs($otherBusiness)
            ->get(route('business.marketplace', ['listing_type' => 'buy']))
            ->assertOk()
            ->assertSee('Item Reusable Sell')
            ->assertDontSee('Item Not Reusable Free');

        // Other business on marketplace should ONLY see reusable items, not_reusable is hidden
        $this->actingAs($otherBusiness)
            ->get(route('business.marketplace'))
            ->assertOk()
            ->assertSee('Item Reusable Sell')
            ->assertDontSee('Item Not Reusable Free');

        // My Listings only shows reusable published items, even with an old filter.
        $this->actingAs($business)
            ->get(route('business.marketplace', ['my_listings' => '1', 'reusability' => 'not_reusable']))
            ->assertOk()
            ->assertDontSee('Item Not Reusable Free');
    }

    public function test_cannot_list_not_reusable_inventory_item_on_marketplace(): void
    {
        $business = $this->createApprovedBusiness();

        $images = [
            UploadedFile::fake()->image('scrap.jpg', 600, 400),
        ];

        // Create a not_reusable inventory item
        $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Internal Scrap Materials',
            'description' => 'Plastic cutoffs strictly for internal tracking',
            'category' => 'Plastics & rubber',
            'quantity' => 100,
            'unit' => 'kg',
            'reusability' => 'not_reusable',
            'images' => $images,
        ]);

        $item = InventoryItem::where('name', 'Internal Scrap Materials')->firstOrFail();
        $this->assertSame('not_reusable', $item->reusability);
        $this->assertSame('Plastic cutoffs strictly for internal tracking', $item->description);

        // Attempting to list on marketplace should fail validation
        $response = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'inventory_item_id' => $item->id,
            'title' => 'Scrap listing attempt',
            'category' => 'Plastics & rubber',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'quantity' => 20,
            'unit' => 'kg',
            'price' => 10,
        ]);

        $response->assertSessionHasErrors(['reusability']);
        $this->assertDatabaseMissing('item', ['title' => 'Scrap listing attempt']);
    }

    public function test_inventory_creation_requires_at_least_one_image_and_max_five(): void
    {
        $business = $this->createApprovedBusiness();

        // 1. Zero images should fail
        $responseZero = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'No Image Material',
            'category' => 'Plastics & rubber',
            'quantity' => 100,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'images' => [],
        ]);
        $responseZero->assertSessionHasErrors(['images']);

        // 2. Six images (more than 5) should fail
        $sixImages = [
            UploadedFile::fake()->image('img1.jpg'),
            UploadedFile::fake()->image('img2.jpg'),
            UploadedFile::fake()->image('img3.jpg'),
            UploadedFile::fake()->image('img4.jpg'),
            UploadedFile::fake()->image('img5.jpg'),
            UploadedFile::fake()->image('img6.jpg'),
        ];
        $responseSix = $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Too Many Images Material',
            'category' => 'Plastics & rubber',
            'quantity' => 100,
            'unit' => 'kg',
            'reusability' => 'reusable',
            'images' => $sixImages,
        ]);
        $responseSix->assertSessionHasErrors(['images']);
    }

    public function test_owner_can_delete_marketplace_listing_and_others_cannot(): void
    {
        $owner = $this->createApprovedBusiness('Owner Business');
        $other = $this->createApprovedBusiness('Other Business');

        $listing = MarketplaceListing::create([
            'business_id' => $owner->id,
            'title' => 'Sample Listing',
            'description' => 'Test listing description',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'quantity' => 10,
            'unit' => 'pcs',
            'price' => 15,
            'is_free' => false,
            'location_city' => 'Klang',
            'location_state' => 'Selangor',
            'status' => 'active',
        ]);

        // Other business cannot delete
        $this->actingAs($other)
            ->delete(route('business.marketplace.destroy', $listing))
            ->assertForbidden();

        $this->assertDatabaseHas('item', ['id' => $listing->id]);

        // Owner can delete
        $this->actingAs($owner)
            ->delete(route('business.marketplace.destroy', $listing))
            ->assertRedirect();

        $this->assertDatabaseMissing('item', ['id' => $listing->id]);
    }

    public function test_reusable_inventory_item_saves_to_inventory_only_until_explicitly_published(): void
    {
        $business = $this->createApprovedBusiness('BioGreen Manufacturing');
        $viewer = $this->createApprovedBusiness('Buyer Corp');

        $image = UploadedFile::fake()->image('pallet.jpg', 400, 400);

        $response = $this->actingAs($business)
            ->from(route('business.inventory'))
            ->post(route('business.inventory.store'), [
                'name' => 'Grade-A Reusable Wooden Pallets',
                'description' => 'Heat treated pine wood pallets, certified for logistics reuse.',
                'category' => 'Furniture & wood products',
                'quantity' => 80,
                'unit' => 'pcs',
                'reusability' => 'reusable',
                'condition' => 'Used',
                'images' => [$image],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('business.inventory'));

        // Check Inventory Item was created in Inventory ONLY (NOT auto-published)
        $item = InventoryItem::where('name', 'Grade-A Reusable Wooden Pallets')->firstOrFail();
        $this->assertSame('reusable', $item->reusability);
        $this->assertSame('in_stock', $item->getRawOriginal('status'));
        $this->assertNull($item->price);
        $this->assertFalse($item->isPublishedToMarketplace());

        // Not visible on Marketplace or Directory before explicit publish
        $this->flushSession();
        $this->actingAs($viewer)
            ->get(route('business.marketplace'))
            ->assertOk()
            ->assertViewHas('listings', fn ($listings): bool => ! $listings->contains('id', $item->id))
            ->assertDontSee('Grade-A Reusable Wooden Pallets');

        $this->actingAs($viewer)
            ->get(route('business.directory.show', $business))
            ->assertOk()
            ->assertViewHas('listings', fn ($listings): bool => ! $listings->contains('id', $item->id))
            ->assertDontSee('Grade-A Reusable Wooden Pallets');

        // Explicitly publish to Marketplace as a separate action
        $this->actingAs($business)->post(route('business.marketplace.store'), [
            'inventory_item_id' => $item->id,
            'title' => 'Grade-A Reusable Wooden Pallets',
            'description' => 'Heat treated pine wood pallets, certified for logistics reuse.',
            'category' => 'Furniture & wood products',
            'quantity' => 80,
            'unit' => 'pcs',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 35.50,
            'condition' => 'Used',
        ])->assertRedirect(route('business.marketplace'));

        $listing = MarketplaceListing::findOrFail($item->id);
        $this->assertSame('active', $listing->status);
        $this->assertSame('Grade-A Reusable Wooden Pallets', $listing->title);
        $this->assertSame('buy', $listing->listing_type);
        $this->assertSame(35.50, (float) $listing->price);
        $this->assertSame(80.0, (float) $listing->quantity);
        $this->assertSame($business->id, $listing->business_id);
        $this->assertNotNull($listing->image_path);

        // Now visible in Marketplace and Business Directory
        $this->actingAs($viewer)
            ->get(route('business.marketplace'))
            ->assertOk()
            ->assertSee('Grade-A Reusable Wooden Pallets');

        $this->actingAs($viewer)
            ->get(route('business.directory.show', $business))
            ->assertOk()
            ->assertSee('Grade-A Reusable Wooden Pallets');
    }

    public function test_not_reusable_inventory_item_remains_internal_scrap_and_never_lists_on_marketplace(): void
    {
        $business = $this->createApprovedBusiness('Selangor Steel Mills');

        $image = UploadedFile::fake()->image('scrap.jpg', 400, 400);

        $response = $this->actingAs($business)
            ->from(route('business.inventory'))
            ->post(route('business.inventory.store'), [
                'name' => 'Mixed Metal Shavings & Scrap Offcuts',
                'description' => 'Industrial machining shavings from CNC cutting.',
                'category' => 'Metal & fabrication',
                'quantity' => 250,
                'unit' => 'kg',
                'reusability' => 'not_reusable',
                'images' => [$image],
            ]);

        $response->assertSessionHasNoErrors();

        // Check Inventory Item was created as scrap
        $item = InventoryItem::where('name', 'Mixed Metal Shavings & Scrap Offcuts')->firstOrFail();
        $this->assertSame('not_reusable', $item->reusability);

        // Check item in item table is saved as not_reusable scrap
        $this->assertDatabaseHas('item', [
            'id' => $item->id,
            'reusability' => 'not_reusable',
        ]);
        $this->assertDatabaseMissing('item', [
            'id' => $item->id,
            'reusability' => 'reusable',
        ]);

        // Verify other businesses cannot see it on directory or public marketplace
        $viewer = $this->createApprovedBusiness('Buyer Corp');
        $dirResponse = $this->actingAs($viewer)->get(route('business.directory.show', $business));
        $dirResponse->assertOk();
        $dirResponse->assertDontSee('Mixed Metal Shavings & Scrap Offcuts');

        $mktResponse = $this->actingAs($viewer)->get(route('business.marketplace'));
        $mktResponse->assertOk();
        $mktResponse->assertDontSee('Mixed Metal Shavings & Scrap Offcuts');
    }

    public function test_inventory_updates_and_quantity_adjustments_synchronize_to_marketplace(): void
    {
        $business = $this->createApprovedBusiness('Textile Hub Sdn Bhd');
        $image = UploadedFile::fake()->image('fabric.jpg', 400, 400);

        $this->actingAs($business)->post(route('business.inventory.store'), [
            'name' => 'Cotton Fabric Rolls',
            'category' => 'Textiles & apparel',
            'quantity' => 100,
            'unit' => 'm',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'images' => [$image],
        ])->assertSessionHasNoErrors();

        $item = InventoryItem::where('name', 'Cotton Fabric Rolls')->firstOrFail();

        // Publish explicitly to Marketplace first
        $this->actingAs($business)->post(route('business.marketplace.store'), [
            'inventory_item_id' => $item->id,
            'title' => 'Cotton Fabric Rolls',
            'category' => 'Textiles & apparel',
            'quantity' => 100,
            'unit' => 'm',
            'condition' => 'Used',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 12.00,
        ])->assertSessionHasNoErrors();

        $listing = MarketplaceListing::findOrFail($item->id);
        $this->assertSame('active', $listing->status);
        $this->assertSame(100.0, (float) $listing->quantity);

        // Deduct stock to 0 via stock_out adjustment
        $this->actingAs($business)->post(route('business.inventory.adjust', $item), [
            'type' => 'stock_out',
            'quantity' => 100,
        ]);

        $item->refresh();
        $listing->refresh();
        $this->assertSame(0.0, (float) $item->quantity);
        $this->assertSame('out_of_stock', $item->status);
        $this->assertSame('sold', $listing->status);

        // Replenish stock via stock_in adjustment
        $this->actingAs($business)->post(route('business.inventory.adjust', $item), [
            'type' => 'stock_in',
            'quantity' => 50,
        ]);

        $item->refresh();
        $listing->refresh();
        $this->assertSame(50.0, (float) $item->quantity);
        $this->assertSame('in_stock', $item->status);
        $this->assertSame('active', $listing->status);

        // Change reusability to not_reusable (scrap) via update
        $this->actingAs($business)->put(route('business.inventory.update', $item), [
            'name' => 'Damaged Cotton Fabric Scrap',
            'category' => 'Textiles & apparel',
            'unit' => 'm',
            'condition' => 'Damaged',
            'reusability' => 'not_reusable',
        ]);

        $item->refresh();
        $this->assertSame('not_reusable', $item->reusability);
        $this->assertDatabaseMissing('item', [
            'id' => $item->id,
            'reusability' => 'reusable',
        ]);
        $this->assertDatabaseHas('item', [
            'id' => $item->id,
            'reusability' => 'not_reusable',
        ]);
    }

    public function test_marketplace_direct_listing_automatically_creates_inventory_item(): void
    {
        $business = $this->createApprovedBusiness('Packaging Specialist');
        $image = UploadedFile::fake()->image('cartons.jpg', 400, 400);

        $response = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Excess Corrugated Cartons',
            'description' => 'New double-wall shipping boxes.',
            'category' => 'Packaging & paper',
            'quantity' => 200,
            'unit' => 'Box',
            'condition' => 'New',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 4.50,
            'images' => [$image],
        ]);

        $response->assertSessionHasNoErrors();

        // Marketplace listing created and connected to the same item row in inventory
        $listing = MarketplaceListing::where('title', 'Excess Corrugated Cartons')->firstOrFail();
        $item = InventoryItem::find($listing->id);
        $this->assertNotNull($item);
        $this->assertSame('Excess Corrugated Cartons', $item->name);
        $this->assertSame('Packaging & paper', $item->category);
        $this->assertSame(200.0, (float) $item->quantity);
        $this->assertSame('reusable', $item->reusability);
        $this->assertSame(4.50, (float) $item->price);
    }

    public function test_end_to_end_marketplace_exchange_transaction_flow(): void
    {
        $seller = $this->createApprovedBusiness('Seller Eco Print');
        $buyer = $this->createApprovedBusiness('Buyer Packaging Ltd');

        // 1. Seller adds reusable item into inventory and publishes it to Marketplace
        $image = UploadedFile::fake()->image('paper.jpg', 400, 400);
        $this->actingAs($seller)->post(route('business.inventory.store'), [
            'name' => 'Recycled Kraft Sheets',
            'description' => 'Heavyweight 120gsm kraft paper.',
            'category' => 'Packaging & paper',
            'quantity' => 100,
            'unit' => 'Box',
            'condition' => 'New',
            'reusability' => 'reusable',
            'images' => [$image],
        ])->assertSessionHasNoErrors();

        $item = Item::where('name', 'Recycled Kraft Sheets')->firstOrFail();

        $this->actingAs($seller)->post(route('business.marketplace.store'), [
            'inventory_item_id' => $item->id,
            'title' => 'Recycled Kraft Sheets',
            'description' => 'Heavyweight 120gsm kraft paper.',
            'category' => 'Packaging & paper',
            'quantity' => 100,
            'unit' => 'Box',
            'condition' => 'New',
            'reusability' => 'reusable',
            'listing_type' => 'buy',
            'price' => 25.00,
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('active', $item->status);

        // 2. Buyer views marketplace and requests an exchange / order for 30 reams
        $exchangeResponse = $this->actingAs($buyer)->post(route('business.marketplace.exchange', $item), [
            'quantity' => 30,
            'notes' => 'Please arrange delivery to Shah Alam plant.',
        ]);

        $exchangeResponse->assertRedirect(route('business.transactions', ['tab' => 'purchases']));
        $exchangeResponse->assertSessionHas('status');

        $exchange = MaterialExchange::where('item_id', $item->id)->firstOrFail();
        $this->assertSame($seller->id, $exchange->provider_business_id);
        $this->assertSame($buyer->id, $exchange->requester_business_id);
        $this->assertSame(30.0, (float) $exchange->quantity);
        $this->assertSame(750.00, (float) $exchange->total_price);
        $this->assertSame('pending', $exchange->payment_status);

        // 3. Seller requests payment (generates invoice notification)
        $this->actingAs($seller)
            ->post(route('business.transactions.request-payment', $exchange))
            ->assertSessionHas('status');

        $exchange->refresh();
        $this->assertSame('requested', $exchange->payment_status);
        $this->assertNotNull($exchange->payment_requested_at);

        // 4. Buyer marks as paid
        $this->actingAs($buyer)
            ->post(route('business.transactions.mark-paid', $exchange), [
                'payment_reference' => 'FPX-TEST-9988',
            ])
            ->assertSessionHas('status');

        $exchange->refresh();
        $this->assertSame('paid', $exchange->payment_status);
        $this->assertSame('confirmed', $exchange->status);
        $this->assertSame('confirmed', $exchange->delivery_status);
        $this->assertSame('FPX-TEST-9988', $exchange->payment_reference);

        // 5. Verify quantity was automatically deducted from item (100 - 30 = 70)
        $item->refresh();
        $this->assertSame(70.0, (float) $item->quantity);
        $this->assertSame('active', $item->status);

        // 6. Verify inventory transaction record was created
        $invTrans = InventoryTransaction::where('inventory_item_id', $item->id)->where('transaction_type', 'stock_out')->first();
        $this->assertNotNull($invTrans);
        $this->assertSame(-30.0, (float) $invTrans->quantity_change);
        $this->assertSame(70.0, (float) $invTrans->quantity_after);

        // 7. Buyer leaves review for the seller on this item
        $reviewResponse = $this->actingAs($buyer)->post(route('business.listings.reviews', $item), [
            'rating' => 5,
            'comment' => 'Excellent quality kraft sheets and prompt dispatch!',
            'exchange_type' => 'buy',
        ]);
        $reviewResponse->assertRedirect();

        $review = MarketplaceReview::where('item_id', $item->id)->firstOrFail();
        $this->assertSame(5, $review->rating);
        $this->assertSame($seller->id, $review->seller_business_id);
        $this->assertSame($buyer->id, $review->reviewer_business_id);

        $otherListing = $item->replicate();
        $otherListing->save();

        MarketplaceReview::create([
            'item_id' => $otherListing->id,
            'seller_business_id' => $seller->id,
            'reviewer_business_id' => $buyer->id,
            'rating' => 4,
            'comment' => 'Review for another item.',
            'exchange_type' => 'buy',
        ]);

        $nonBuyer = $this->createApprovedBusiness('Unverified Reviewer');
        MarketplaceReview::create([
            'item_id' => $item->id,
            'seller_business_id' => $seller->id,
            'reviewer_business_id' => $nonBuyer->id,
            'rating' => 1,
            'comment' => 'Review without a purchase.',
            'exchange_type' => 'buy',
        ]);

        $this->get(route('business.marketplace'))->assertSee('View item reviews, rated 5.0 out of 5');

        $this->getJson(route('business.marketplace.seller-reviews', $item))
            ->assertOk()
            ->assertJsonCount(1, 'reviews')
            ->assertJsonPath('reviews.0.reviewer', 'Buyer Packaging Ltd')
            ->assertJsonPath('reviews.0.rating', 5)
            ->assertJsonPath('reviews.0.comment', 'Excellent quality kraft sheets and prompt dispatch!')
            ->assertJsonPath('next', null);

        $item->update(['quantity' => 0, 'status' => 'sold']);

        $this->get(route('business.marketplace', ['stock_status' => 'in_stock']))
            ->assertOk()
            ->assertSee('id="listing-card-'.$otherListing->id.'"', false)
            ->assertDontSee('id="listing-card-'.$item->id.'"', false);

        $this->get(route('business.marketplace', ['stock_status' => 'out_of_stock']))
            ->assertOk()
            ->assertSee('id="listing-card-'.$item->id.'"', false)
            ->assertDontSee('id="listing-card-'.$otherListing->id.'"', false);

        $this->get(route('business.marketplace'))
            ->assertOk()
            ->assertSee('disabled>Out of Stock</button>', false)
            ->assertSee('View item reviews, rated 5.0 out of 5');

        $this->getJson(route('business.marketplace.seller-reviews', $item))
            ->assertOk()
            ->assertJsonCount(1, 'reviews')
            ->assertJsonPath('reviews.0.comment', 'Excellent quality kraft sheets and prompt dispatch!');

        $item->update(['status' => 'inactive']);

        $this->getJson(route('business.marketplace.seller-reviews', $item))
            ->assertNotFound();
    }

    public function test_marketplace_exchange_guardrails_and_visibility(): void
    {
        $seller = $this->createApprovedBusiness('Alpha Tech');
        $buyer = $this->createApprovedBusiness('Beta Systems');

        // Reusable item
        $reusableItem = Item::create([
            'business_id' => $seller->id,
            'name' => 'Aluminum Offcuts',
            'category' => 'Metals & machinery',
            'quantity' => 50,
            'unit' => 'kg',
            'price' => 12.00,
            'reusability' => 'reusable',
            'status' => 'active',
        ]);

        // Scrap non-reusable item
        $scrapItem = Item::create([
            'business_id' => $seller->id,
            'name' => 'Contaminated Solvent Sludge',
            'category' => 'Chemicals & solvents',
            'quantity' => 20,
            'unit' => 'L',
            'price' => 0.00,
            'reusability' => 'not_reusable',
            'status' => 'in_stock',
        ]);

        // 1. Seller cannot order their own item
        $this->actingAs($seller)
            ->post(route('business.marketplace.exchange', $reusableItem), ['quantity' => 5])
            ->assertStatus(400);

        // 2. Buyer cannot order non-reusable scrap item
        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $scrapItem), ['quantity' => 5])
            ->assertStatus(400);

        // 3. Buyer cannot request more than available stock
        $this->actingAs($buyer)
            ->post(route('business.marketplace.exchange', $reusableItem), ['quantity' => 999])
            ->assertSessionHasErrors(['quantity']);

        // 4. Check Overview directory: Buyer sees Seller, but NOT themselves in the directory
        $overviewResponse = $this->actingAs($buyer)->get(route('business.dashboard'));
        $overviewResponse->assertOk();
        $overviewResponse->assertSee('Alpha Tech');
        $this->assertCount(1, $overviewResponse->viewData('businesses'));
        $this->assertSame('Alpha Tech', $overviewResponse->viewData('businesses')[0]['business_name']);

        // 5. Check Marketplace: Reusable item is visible, scrap is NOT visible
        $marketplaceResponse = $this->actingAs($buyer)->get(route('business.marketplace'));
        $marketplaceResponse->assertOk();
        $marketplaceResponse->assertSee('Aluminum Offcuts');
        $marketplaceResponse->assertDontSee('Contaminated Solvent Sludge');
    }
}
