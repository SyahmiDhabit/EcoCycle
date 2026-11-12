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
                'ai_recommendation' => 'Secondary smelting and ingot billet casting',
            ]);

        $response->assertRedirect('/business/waste-monitoring');

        $this->assertDatabaseHas('waste_records', [
            'business_id' => $biz->id,
            'inventory_item_id' => $item->id,
            'material_name' => 'Steel Machine Shavings',
            'facility_name' => 'Shan Poornam Metals Sdn Bhd',
            'status' => 'scheduled_pickup',
            'is_diverted' => true,
        ]);
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
            'disposal_method' => 'Biomass granulation',
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
                'facility_name' => 'Merces Bio-Pelletizing Facility',
                'facility_location' => 'Rawang, Selangor',
            ]);

        $updateResponse->assertRedirect('/business/waste-monitoring');
        $this->assertDatabaseHas('waste_records', [
            'id' => $record->id,
            'status' => 'recycled',
            'is_diverted' => true,
        ]);
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
            'listing_id' => $listing->id,
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
        $this->assertDatabaseHas('material_exchanges', [
            'id' => $exchange->id,
            'payment_status' => 'requested',
        ]);

        // Buyer or seller marks paid
        $paidResponse = $this->actingAs($buyer)
            ->post("/business/transactions/{$exchange->id}/mark-paid", [
                'payment_reference' => 'FPX-TEST-9988',
            ]);

        $paidResponse->assertRedirect();
        $this->assertDatabaseHas('material_exchanges', [
            'id' => $exchange->id,
            'payment_status' => 'paid',
            'payment_reference' => 'FPX-TEST-9988',
            'status' => 'completed',
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
        $this->assertDatabaseHas('marketplace_listings', [
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
        $this->assertDatabaseHas('marketplace_reviews', [
            'listing_id' => $listing->id,
            'seller_business_id' => $seller->id,
            'reviewer_business_id' => $buyer->id,
            'rating' => 5,
        ]);
    }

    public function test_reports_page_displays_financial_and_circular_metrics(): void
    {
        $biz = $this->createApprovedBusiness('Green Paper Works');

        $response = $this->actingAs($biz)->get('/business/reports');
        $response->assertStatus(200);
        $response->assertSee('Financial, ESG');
        $response->assertSee('Circularity Rate');
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
}
