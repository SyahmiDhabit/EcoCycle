<?php

namespace Tests\Feature;

use App\Models\Administrator;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\MarketplaceListing;
use App\Models\MaterialExchange;
use App\Models\User;
use App\Models\WasteRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_belongs_to_administrator_reviewer(): void
    {
        $admin = Administrator::create([
            'name' => 'Admin Officer',
            'email' => 'admin@ecocycle.test',
            'password' => 'password123',
        ]);

        $business = User::create([
            'name' => 'greensolutions',
            'email' => 'contact@greensolutions.com',
            'password' => 'password123',
            'business_name' => 'Green Solutions Sdn Bhd',
            'business_registration_number' => '202601009999',
            'account_status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->assertNotNull($business->reviewedBy);
        $this->assertEquals($admin->id, $business->reviewedBy->id);
        $this->assertTrue($admin->reviewedBusinesses->contains($business));
    }

    public function test_inventory_and_transactions_cascade_on_business_delete(): void
    {
        $business = User::create([
            'name' => 'ecocorp',
            'email' => 'eco@corp.com',
            'password' => 'password123',
            'business_name' => 'EcoCorp Enterprise',
        ]);

        $item = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Cardboard Boxes',
            'category' => 'Packaging',
            'quantity' => 100,
            'unit' => 'pcs',
        ]);

        $tx = InventoryTransaction::create([
            'business_id' => $business->id,
            'inventory_item_id' => $item->id,
            'transaction_type' => 'stock_in',
            'quantity_change' => 50,
            'quantity_after' => 100,
            'notes' => 'Initial bulk order',
        ]);

        $this->assertEquals($business->id, $item->business->id);
        $this->assertEquals($item->id, $tx->inventoryItem->id);
        $this->assertEquals($business->id, $tx->business->id);
        $this->assertTrue($item->transactions->contains($tx));

        // When item is deleted, transactions cascade
        $item->delete();
        $this->assertDatabaseMissing('inventory_transaction', ['id' => $tx->id]);
    }

    public function test_waste_record_foreign_keys_and_null_on_item_delete(): void
    {
        $business = User::create([
            'name' => 'cleanfuture',
            'email' => 'clean@future.com',
            'password' => 'password123',
            'business_name' => 'Clean Future Ltd',
        ]);

        $item = InventoryItem::create([
            'business_id' => $business->id,
            'name' => 'Timber Scrap',
            'category' => 'Materials',
            'quantity' => 20,
            'unit' => 'kg',
        ]);

        $waste = WasteRecord::create([
            'business_id' => $business->id,
            'inventory_item_id' => $item->id,
            'material_name' => 'Timber Offcuts',
            'waste_type' => 'Wood',
            'quantity' => 15.5,
            'unit' => 'kg',
            'disposal_method' => 'Recycled',
            'is_diverted' => true,
            'cost_or_savings' => 150.00,
            'recorded_at' => now()->toDateString(),
        ]);

        $this->assertEquals($item->id, $waste->inventoryItem->id);
        $this->assertEquals($business->id, $waste->business->id);
        $this->assertTrue($business->wasteRecords->contains($waste));

        // Delete inventory item -> waste record preserves history but sets inventory_item_id to NULL
        $item->delete();
        $this->assertDatabaseHas('waste_record', [
            'id' => $waste->id,
            'inventory_item_id' => null,
        ]);
    }

    public function test_marketplace_listing_and_material_exchange_relationships(): void
    {
        $provider = User::create([
            'name' => 'palletseller',
            'email' => 'seller@pallets.com',
            'password' => 'password123',
            'business_name' => 'Pallet Supplies Bhd',
        ]);

        $requester = User::create([
            'name' => 'craftbuyer',
            'email' => 'buyer@crafts.com',
            'password' => 'password123',
            'business_name' => 'Creative Crafts Enterprise',
        ]);

        $listing = MarketplaceListing::create([
            'business_id' => $provider->id,
            'title' => 'Reclaimed Wooden Pallets',
            'description' => 'Heavy duty pallets suitable for reuse',
            'category' => 'Wood',
            'quantity' => 50,
            'unit' => 'pcs',
            'price' => 15.00,
            'is_free' => false,
            'condition' => 'Used - Good',
            'location_city' => 'Shah Alam',
            'location_state' => 'Selangor',
            'status' => 'active',
        ]);

        $exchange = MaterialExchange::create([
            'listing_id' => $listing->id,
            'provider_business_id' => $provider->id,
            'requester_business_id' => $requester->id,
            'quantity' => 10,
            'unit' => 'pcs',
            'total_price' => 150.00,
            'status' => 'pending',
            'notes' => 'Pickup scheduled for Friday afternoon',
        ]);

        $this->assertEquals($provider->id, $exchange->provider->id);
        $this->assertEquals($requester->id, $exchange->requester->id);
        $this->assertEquals($listing->id, $exchange->listing->id);
        $this->assertTrue($listing->exchanges->contains($exchange));
        $this->assertTrue($provider->materialExchangesAsProvider->contains($exchange));
        $this->assertTrue($requester->materialExchangesAsRequester->contains($exchange));

        // When listing is deleted, exchange records cascade
        $listing->delete();
        $this->assertDatabaseMissing('material_exchange', ['id' => $exchange->id]);
    }
}
