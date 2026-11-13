<?php

namespace Tests\Feature;

use App\Mail\BusinessApplicationDecision;
use App\Mail\BusinessApplicationSubmitted;
use App\Models\Administrator;
use App\Models\AdminNotification;
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

        Mail::assertSent(BusinessApplicationDecision::class, function ($mail) use ($business) {
            $isToBusiness = $mail->hasTo($business->email);
            $hasCorrectSubject = $mail->envelope()->subject === 'Update regarding your EcoCycle business registration: Application Approved';
            $isApproved = $mail->decision === 'approved';

            $rendered = $mail->render();
            $containsBizName = str_contains($rendered, 'Eco Plastics Sdn Bhd');
            $containsStatus = str_contains($rendered, 'Approved');

            return $isToBusiness && $hasCorrectSubject && $isApproved && $containsBizName && $containsStatus;
        });
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
        Mail::assertNotSent(BusinessApplicationSubmitted::class);

        // Rejection is communicated only in email
        $this->assertDatabaseMissing('business_notification', [
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
        $this->assertDatabaseHas('business_notification', [
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

        $this->assertDatabaseHas('business_notification', [
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

        AdminNotification::send(
            'new_registration',
            'New Business Registration',
            'Solar Biofuel Enterprise has registered for review.',
            '/admin/dashboard',
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

        $this->assertDatabaseHas('admin_notification', [
            'type' => 'new_registration',
            'business_id' => $business->id,
            'is_read' => true,
        ]);
    }

    public function test_admin_can_request_changes_and_email_is_sent_with_remarks_and_status(): void
    {
        Mail::fake();

        $admin = Administrator::create([
            'name' => 'Reviewer Admin',
            'email' => 'reviewer2@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'name' => 'sarah_biz',
            'business_name' => 'Sarah Eco Tech',
            'email' => 'sarah@ecotech.my',
            'account_status' => 'pending',
            'business_registration_number' => '202301019999 (1234567-T)',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.business.reject', $business), [
                'review_note' => 'Please provide page 2 of your SSM certificate clearly.',
                'action_type' => 'request_changes',
            ]);

        $response->assertSessionHas('status', 'Sarah Eco Tech needs changes before approval.');

        $business->refresh();
        $this->assertSame('rejected', $business->account_status);
        $this->assertSame('Please provide page 2 of your SSM certificate clearly.', $business->review_note);

        Mail::assertSent(BusinessApplicationDecision::class, function ($mail) {
            $isToBusiness = $mail->hasTo('sarah@ecotech.my');
            $hasCorrectSubject = $mail->envelope()->subject === 'Update regarding your EcoCycle business registration: Changes Requested';
            $isChangesRequested = $mail->decision === 'changes_requested';

            $rendered = $mail->render();
            $containsRemarks = str_contains($rendered, 'Please provide page 2 of your SSM certificate clearly.');
            $containsBizName = str_contains($rendered, 'Sarah Eco Tech');
            $containsStatus = str_contains($rendered, 'Changes Required');

            return $isToBusiness && $hasCorrectSubject && $isChangesRequested && $containsRemarks && $containsBizName && $containsStatus;
        });
    }

    public function test_admin_can_reject_registration_and_email_is_sent_with_rejection_reason_and_status(): void
    {
        Mail::fake();

        $admin = Administrator::create([
            'name' => 'Reviewer Admin',
            'email' => 'reviewer3@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'name' => 'banned_biz',
            'business_name' => 'Invalid Timber Ltd',
            'email' => 'invalid@timber.my',
            'account_status' => 'pending',
            'business_registration_number' => '202001099999 (9999999-X)',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.business.reject', $business), [
                'review_note' => 'SSM certificate registration does not match company registry.',
                'action_type' => 'reject',
            ]);

        $response->assertSessionHas('status', 'Invalid Timber Ltd registration has been rejected.');

        $business->refresh();
        $this->assertSame('rejected', $business->account_status);

        Mail::assertSent(BusinessApplicationDecision::class, function ($mail) {
            $isToBusiness = $mail->hasTo('invalid@timber.my');
            $hasCorrectSubject = $mail->envelope()->subject === 'Update regarding your EcoCycle business registration: Application Rejected';
            $isRejected = $mail->decision === 'rejected';

            $rendered = $mail->render();
            $containsReason = str_contains($rendered, 'SSM certificate registration does not match company registry.');
            $containsBizName = str_contains($rendered, 'Invalid Timber Ltd');
            $containsStatus = str_contains($rendered, 'Registration Rejected');

            return $isToBusiness && $hasCorrectSubject && $isRejected && $containsReason && $containsBizName && $containsStatus;
        });
    }

    public function test_duplicate_status_update_does_not_send_duplicate_emails(): void
    {
        Mail::fake();

        $admin = Administrator::create([
            'name' => 'Reviewer Admin',
            'email' => 'reviewer4@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'business_name' => 'Duplicate Test Corp',
            'email' => 'dupe@testcorp.my',
            'account_status' => 'pending',
        ]);

        // First review note
        $this->actingAs($admin, 'admin')
            ->post(route('admin.business.reject', $business), [
                'review_note' => 'First note',
                'action_type' => 'request_changes',
            ]);

        Mail::assertSent(BusinessApplicationDecision::class, 1);

        // Immediate duplicate submission with the same note
        $this->actingAs($admin, 'admin')
            ->post(route('admin.business.reject', $business), [
                'review_note' => 'First note',
                'action_type' => 'request_changes',
            ]);

        // Still only 1 email sent, no duplicate sent
        Mail::assertSent(BusinessApplicationDecision::class, 1);
    }

    public function test_admin_can_use_dedicated_request_changes_endpoint(): void
    {
        Mail::fake();

        $admin = Administrator::create([
            'name' => 'Reviewer Admin 5',
            'email' => 'reviewer5@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'name' => 'green_metals',
            'business_name' => 'Green Metals Sdn Bhd',
            'email' => 'contact@greenmetals.com',
            'account_status' => 'pending',
            'business_registration_number' => '202401055555 (5555555-M)',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.business.request-changes', $business), [
                'review_note' => 'Please provide complete page 1-3 of SSM profile.',
            ]);

        $response->assertSessionHas('status', 'Green Metals Sdn Bhd needs changes before approval.');

        $business->refresh();
        $this->assertSame('rejected', $business->account_status);
        $this->assertSame('Please provide complete page 1-3 of SSM profile.', $business->review_note);

        Mail::assertSent(BusinessApplicationDecision::class, function ($mail) {
            $isToBusiness = $mail->hasTo('contact@greenmetals.com');
            $hasCorrectSubject = $mail->envelope()->subject === 'Update regarding your EcoCycle business registration: Changes Requested';
            $isChangesRequested = $mail->decision === 'changes_requested';

            $rendered = $mail->render();
            $containsRemarks = str_contains($rendered, 'Please provide complete page 1-3 of SSM profile.');
            $containsBizName = str_contains($rendered, 'Green Metals Sdn Bhd');
            $containsStatus = str_contains($rendered, 'Changes Required');

            return $isToBusiness && $hasCorrectSubject && $isChangesRequested && $containsRemarks && $containsBizName && $containsStatus;
        });
    }

    public function test_admin_can_reapprove_or_resend_approval_email(): void
    {
        Mail::fake();

        $admin = Administrator::create([
            'name' => 'Reviewer Admin 6',
            'email' => 'reviewer6@ecocycle.test',
            'password' => 'secret123',
        ]);

        $business = User::factory()->create([
            'business_name' => 'Circular Rubber Sdn Bhd',
            'email' => 'rubber@circulareco.com',
            'account_status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.business.approve', $business));

        $response->assertSessionHas('status', 'Circular Rubber Sdn Bhd has been approved.');

        Mail::assertSent(BusinessApplicationDecision::class, function ($mail) {
            return $mail->hasTo('rubber@circulareco.com')
                && $mail->decision === 'approved'
                && $mail->envelope()->subject === 'Update regarding your EcoCycle business registration: Application Approved';
        });
    }
}
