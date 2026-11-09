<?php

namespace Tests\Feature;

use App\Mail\BusinessApplicationDecision;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_business_and_foreign_key_is_saved(): void
    {
        Mail::fake();

        $admin = Administrator::create([
            'name' => 'Chief Admin',
            'email' => 'admin@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'business_name' => 'Eco Plastics Sdn Bhd',
            'account_status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.business.approve', $business));

        $response->assertSessionHas('status', 'Eco Plastics Sdn Bhd has been approved.');

        $business->refresh();
        $this->assertEquals('approved', $business->account_status);
        $this->assertEquals($admin->id, $business->reviewed_by);
        $this->assertNotNull($business->reviewed_at);
        $this->assertEquals($admin->id, $business->reviewedBy->id);

        Mail::assertSent(BusinessApplicationDecision::class);
    }

    public function test_admin_can_reject_business_with_note_and_foreign_key_is_saved(): void
    {
        Mail::fake();

        $admin = Administrator::create([
            'name' => 'Reviewer Admin',
            'email' => 'reviewer@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'business_name' => 'Sample Corp',
            'account_status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.business.reject', $business), [
                'review_note' => 'Please upload a clearer SSM certificate copy.',
            ]);

        $response->assertSessionHas('status', 'Sample Corp needs changes before approval.');

        $business->refresh();
        $this->assertEquals('rejected', $business->account_status);
        $this->assertEquals('Please upload a clearer SSM certificate copy.', $business->review_note);
        $this->assertEquals($admin->id, $business->reviewed_by);
        $this->assertNotNull($business->reviewed_at);

        Mail::assertSent(BusinessApplicationDecision::class);
    }
}
