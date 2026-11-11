<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAndInventoryTest extends TestCase
{
    use RefreshDatabase;

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

        $response = $this->actingAs($business)
            ->from(route('business.inventory'))
            ->post(route('business.inventory.store'), [
                'name' => 'Industrial Plastic Pallets',
                'category' => 'Packaging & paper',
                'quantity' => 150,
                'unit' => 'units',
                'minimum_threshold' => 20,
                'reusability' => 'reusable',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('business.inventory'));
        $this->assertDatabaseHas('inventory_items', [
            'business_id' => $business->id,
            'name' => 'Industrial Plastic Pallets',
            'reusability' => 'reusable',
        ]);
    }

    public function test_business_can_create_marketplace_listing_with_buy_borrow_or_free(): void
    {
        $business = $this->createApprovedBusiness();

        // 1. Buy listing
        $buyResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Treated Timber Offcuts',
            'category' => 'Furniture & wood products',
            'reusability' => 'not_reusable',
            'listing_type' => 'buy',
            'price' => 45.00,
            'quantity' => 50,
            'unit' => 'kg',
            'location_city' => 'Klang',
            'location_state' => 'Selangor',
        ]);
        $buyResponse->assertRedirect();
        $this->assertDatabaseHas('marketplace_listings', [
            'business_id' => $business->id,
            'title' => 'Treated Timber Offcuts',
            'listing_type' => 'buy',
            'reusability' => 'not_reusable',
            'is_free' => false,
            'price' => 45.00,
        ]);

        // 2. Borrow listing
        $borrowResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Heavy Duty Reusable Crates',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'borrow',
            'price' => 10.00, // deposit
            'quantity' => 30,
            'unit' => 'crates',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
        ]);
        $borrowResponse->assertRedirect();
        $this->assertDatabaseHas('marketplace_listings', [
            'business_id' => $business->id,
            'title' => 'Heavy Duty Reusable Crates',
            'listing_type' => 'borrow',
            'reusability' => 'reusable',
        ]);

        // 3. Free listing (Take for free)
        $freeResponse = $this->actingAs($business)->post(route('business.marketplace.store'), [
            'title' => 'Clean Shredded Cardboard',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'free',
            'price' => 0.00,
            'quantity' => 200,
            'unit' => 'kg',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
        ]);
        $freeResponse->assertRedirect();
        $this->assertDatabaseHas('marketplace_listings', [
            'business_id' => $business->id,
            'title' => 'Clean Shredded Cardboard',
            'listing_type' => 'free',
            'is_free' => true,
            'price' => 0.00,
        ]);
    }

    public function test_marketplace_filters_by_listing_type_and_reusability(): void
    {
        $business = $this->createApprovedBusiness();

        MarketplaceListing::create([
            'business_id' => $business->id,
            'title' => 'Item Reusable Borrow',
            'description' => 'Test durable borrowing item',
            'category' => 'Packaging & paper',
            'reusability' => 'reusable',
            'listing_type' => 'borrow',
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

        // Filter by borrow
        $this->actingAs($business)
            ->get(route('business.marketplace', ['listing_type' => 'borrow']))
            ->assertOk()
            ->assertSee('Item Reusable Borrow')
            ->assertDontSee('Item Not Reusable Free');

        // Filter by reusability: not_reusable
        $this->actingAs($business)
            ->get(route('business.marketplace', ['reusability' => 'not_reusable']))
            ->assertOk()
            ->assertSee('Item Not Reusable Free')
            ->assertDontSee('Item Reusable Borrow');
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

        $this->assertDatabaseHas('marketplace_listings', ['id' => $listing->id]);

        // Owner can delete
        $this->actingAs($owner)
            ->delete(route('business.marketplace.destroy', $listing))
            ->assertRedirect();

        $this->assertDatabaseMissing('marketplace_listings', ['id' => $listing->id]);
    }
}
