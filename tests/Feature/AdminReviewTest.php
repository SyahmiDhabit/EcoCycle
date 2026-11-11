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

        Mail::assertSent(BusinessApplicationDecision::class, function ($mail) use ($business, $admin) {
            return $mail->hasTo($business->email)
                && ! $mail->hasTo($admin->email)
                && ! $mail->hasTo('ssyahmidhabit@gmail.com');
        });
        Mail::assertNotSent(\App\Mail\BusinessApplicationSubmitted::class);

        // Rejection is communicated only in email
        $this->assertDatabaseMissing('business_notifications', [
            'business_id' => $business->id,
        ]);
    }

    public function test_admin_dashboard_displays_business_profile_and_approval_creates_real_notification(): void
    {
        Mail::fake();

        $admin = Administrator::create([
            'name' => 'Reviewer Admin',
            'email' => 'admin2@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'business_name' => 'Apex Recyclers Sdn Bhd',
            'account_status' => 'pending',
            'profile_image' => 'profile-images/apex_logo.png',
            'city' => 'Shah Alam',
            'state' => 'Selangor',
        ]);

        // Admin sees the business info and profile on the dashboard
        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Apex Recyclers Sdn Bhd')
            ->assertSee('Shah Alam')
            ->assertSee('Selangor')
            ->assertSee('View Profile');

        // Admin approves business
        $this->actingAs($admin, 'admin')
            ->post(route('admin.business.approve', $business))
            ->assertSessionHas('status');

        // Verify a real notification was created in database
        $this->assertDatabaseHas('business_notifications', [
            'business_id' => $business->id,
            'type' => 'verification_approved',
            'title' => 'Business Verification Approved',
            'is_read' => false,
        ]);

        $business->refresh();

        // Business logs in and sees the real notification
        $this->actingAs($business)
            ->get(route('business.dashboard'))
            ->assertOk()
            ->assertSee('Business Verification Approved');

        // Business marks all notifications as read
        $this->actingAs($business)
            ->postJson(route('business.notifications.mark-read'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('business_notifications', [
            'business_id' => $business->id,
            'is_read' => true,
        ]);
    }

    public function test_admin_receives_in_system_notifications_and_can_mark_them_read(): void
    {
        $admin = Administrator::create([
            'name' => 'Notification Admin',
            'email' => 'adminnotif@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'business_name' => 'Solar Biofuel Enterprise',
            'account_status' => 'pending',
        ]);

        \App\Models\AdminNotification::send(
            'new_registration',
            'New Business Registration',
            'Solar Biofuel Enterprise has registered for review.',
            '/admin',
            $business->id
        );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('New Business Registration')
            ->assertSee('Solar Biofuel Enterprise has registered for review.');

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.notifications.mark-read'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'new_registration',
            'business_id' => $business->id,
            'is_read' => true,
        ]);
    }
}
