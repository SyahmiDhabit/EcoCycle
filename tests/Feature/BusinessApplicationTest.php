<?php

namespace Tests\Feature;

use App\Mail\BusinessApplicationSubmitted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_guest_cannot_view_application_page(): void
    {
        $this->get(route('business.application'))
            ->assertRedirect(route('login'));
    }

    public function test_business_can_view_application_status_and_submitted_details(): void
    {
        $business = User::factory()->create([
            'business_name' => 'Eco Green Enterprise',
            'business_registration_number' => '202201099999 (1234567-A)',
            'account_status' => 'pending',
            'industry' => 'Manufacturing',
            'business_phone' => '+60 12-345 6789',
            'registered_address' => '10 Jalan Hijau',
            'city' => 'Petaling Jaya',
            'postcode' => '47300',
            'state' => 'Selangor',
            'name' => 'Sara Tan',
            'ssm_certificate_path' => 'business-verification/cert.pdf',
            'ssm_profile_path' => 'business-verification/profile.pdf',
        ]);

        Storage::disk('local')->put('business-verification/cert.pdf', 'dummy content');
        Storage::disk('local')->put('business-verification/profile.pdf', 'dummy profile');

        $this->actingAs($business)
            ->get(route('business.application'))
            ->assertOk()
            ->assertSee('Eco Green Enterprise')
            ->assertSee('202201099999 (1234567-A)')
            ->assertSee('Manufacturing')
            ->assertSee('Sara Tan')
            ->assertSee('Verification pending')
            ->assertSee(route('business.application.document', 'certificate'))
            ->assertSee(route('business.application.document', 'profile'));
    }

    public function test_business_can_update_application_details_and_resubmit(): void
    {
        Mail::fake();

        $business = User::factory()->create([
            'business_name' => 'Old Business Name',
            'business_registration_number' => '202001011111 (999999-X)',
            'account_status' => 'rejected',
            'review_note' => 'Please provide updated company address.',
            'industry' => 'Other',
            'registered_address' => 'Old Address',
            'city' => 'Klang',
            'postcode' => '41000',
            'state' => 'Selangor',
            'name' => 'Old Name',
            'business_phone' => '+60 3-1234 5678',
            'ssm_certificate_path' => 'business-verification/old_cert.pdf',
            'ssm_profile_path' => 'business-verification/old_profile.pdf',
        ]);

        Storage::disk('local')->put('business-verification/old_cert.pdf', 'old cert content');
        Storage::disk('local')->put('business-verification/old_profile.pdf', 'old profile content');

        $response = $this->actingAs($business)
            ->post(route('business.application.update'), [
                'business_name' => 'New Business Name Sdn Bhd',
                'business_registration_number' => '202001011111 (999999-X)',
                'industry' => 'Packaging & paper',
                'business_phone' => '+60 3-9876 5432',
                'registered_address' => '22 New Industrial Park',
                'city' => 'Shah Alam',
                'postcode' => '40000',
                'state' => 'Selangor',
                'name' => 'New Contact Person',
                'ssm_profile' => UploadedFile::fake()->create('new_profile.pdf', 200, 'application/pdf'),
            ]);

        $response->assertSessionHas('status');

        $updated = $business->fresh();
        $this->assertSame('New Business Name Sdn Bhd', $updated->business_name);
        $this->assertSame('Packaging & paper', $updated->industry);
        $this->assertSame('New Contact Person', $updated->name);
        $this->assertSame('22 New Industrial Park', $updated->registered_address);
        $this->assertSame('pending', $updated->account_status);
        $this->assertNull($updated->review_note);

        $this->assertFalse(Storage::disk('local')->exists('business-verification/old_profile.pdf'));
        $this->assertTrue(Storage::disk('local')->exists($updated->ssm_profile_path));

        Mail::assertSent(BusinessApplicationSubmitted::class, function ($mail) use ($updated) {
            return $mail->business->id === $updated->id && $mail->isUpdate === true;
        });

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'application_resubmitted',
            'business_id' => $updated->id,
            'is_read' => false,
        ]);
    }

    public function test_rejected_business_can_modify_application_and_resubmit(): void
    {
        Mail::fake();

        $business = User::factory()->create([
            'business_name' => 'Original Name',
            'business_registration_number' => '202001011111 (999999-X)',
            'account_status' => 'rejected',
            'review_note' => 'Please provide clear SSM registration certificate.',
            'industry' => 'Other',
            'registered_address' => 'Old Address',
            'city' => 'Klang',
            'postcode' => '41000',
            'state' => 'Selangor',
            'name' => 'Old Contact',
            'business_phone' => '+60 3-1234 5678',
        ]);

        $response = $this->actingAs($business)
            ->post(route('business.application.update'), [
                'business_name' => 'Updated Name Sdn Bhd',
                'business_registration_number' => '202001011111 (999999-X)',
                'industry' => 'Packaging & paper',
                'business_phone' => '+60 3-9876 5432',
                'registered_address' => '22 New Industrial Park',
                'city' => 'Shah Alam',
                'postcode' => '40000',
                'state' => 'Selangor',
                'name' => 'Updated Contact Person',
                'ssm_certificate' => UploadedFile::fake()->create('new_cert.pdf', 200, 'application/pdf'),
            ]);

        $response->assertSessionHas('status');

        $updated = $business->fresh();
        $this->assertSame('Updated Name Sdn Bhd', $updated->business_name);
        $this->assertSame('pending', $updated->account_status);
        $this->assertNull($updated->review_note);
        $this->assertNotNull($updated->ssm_certificate_path);

        Mail::assertSent(BusinessApplicationSubmitted::class, function ($mail) use ($updated) {
            return $mail->business->id === $updated->id && $mail->isUpdate === true;
        });

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'application_resubmitted',
            'business_id' => $updated->id,
            'is_read' => false,
        ]);
    }

    public function test_rejected_business_signing_in_is_redirected_to_verification_to_fix_issues(): void
    {
        $business = User::factory()->create([
            'email' => 'rejected@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('Secret123'),
            'account_status' => 'rejected',
            'review_note' => 'The uploaded SSM was unreadable. Please re-upload.',
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => 'rejected@example.com',
            'password' => 'Secret123',
        ]);

        $response->assertRedirect(route('business.dashboard'));
        $this->assertAuthenticatedAs($business);

        $this->actingAs($business)
            ->get(route('business.dashboard'))
            ->assertOk()
            ->assertSee('Changes required')
            ->assertSee('The uploaded SSM was unreadable. Please re-upload.')
            ->assertSee('Update application details &amp; fix issues', false);
    }

    public function test_rejected_business_can_delete_application_data_and_restart(): void
    {
        $business = User::factory()->create([
            'account_status' => 'rejected',
            'profile_image' => 'profile-images/del_avatar.png',
            'ssm_certificate_path' => 'business-verification/del_cert.pdf',
            'ssm_profile_path' => 'business-verification/del_profile.pdf',
        ]);

        Storage::disk('public')->put('profile-images/del_avatar.png', 'avatar data');
        Storage::disk('local')->put('business-verification/del_cert.pdf', 'cert data');
        Storage::disk('local')->put('business-verification/del_profile.pdf', 'profile data');

        $response = $this->actingAs($business)
            ->delete(route('business.application.destroy'));

        $response->assertRedirect(route('register'));
        $response->assertSessionHas('status');

        $this->assertFalse(Storage::disk('public')->exists('profile-images/del_avatar.png'));
        $this->assertFalse(Storage::disk('local')->exists('business-verification/del_cert.pdf'));
        $this->assertFalse(Storage::disk('local')->exists('business-verification/del_profile.pdf'));
        $this->assertDatabaseMissing('business', ['id' => $business->id]);
    }

    public function test_business_can_download_their_own_documents(): void
    {
        $business = User::factory()->create([
            'ssm_certificate_path' => 'business-verification/my_cert.pdf',
            'ssm_profile_path' => 'business-verification/my_profile.pdf',
        ]);

        Storage::disk('local')->put('business-verification/my_cert.pdf', 'cert file bytes');
        Storage::disk('local')->put('business-verification/my_profile.pdf', 'profile file bytes');

        $this->actingAs($business)
            ->get(route('business.application.document', 'certificate'))
            ->assertOk()
            ->assertDownload('my_cert.pdf');

        $this->actingAs($business)
            ->get(route('business.application.document', 'profile'))
            ->assertOk()
            ->assertDownload('my_profile.pdf');
    }

    public function test_business_can_update_profile_image(): void
    {
        $business = User::factory()->create([
            'business_name' => 'Original Biz',
            'business_registration_number' => '202301011111 (1000000-A)',
            'account_status' => 'pending',
            'industry' => 'Manufacturing',
            'business_phone' => '+60 12-345 6789',
            'registered_address' => '10 Jalan Hijau',
            'city' => 'Petaling Jaya',
            'postcode' => '47300',
            'state' => 'Selangor',
            'name' => 'Original Contact',
        ]);

        $response = $this->actingAs($business)
            ->from(route('business.application'))
            ->post(route('business.application.update'), [
                'business_name' => 'Updated Biz Name',
                'business_registration_number' => '202301011111 (1000000-A)',
                'industry' => 'Packaging & paper',
                'business_phone' => '+60 12-999 8888',
                'registered_address' => '50 Jalan Baru',
                'city' => 'Klang',
                'postcode' => '41000',
                'state' => 'Selangor',
                'name' => 'New Contact',
                'profile_image' => UploadedFile::fake()->image('new_logo.png', 150, 150),
            ]);

        $response->assertRedirect(route('business.application'))
            ->assertSessionHas('status');

        $business->refresh();
        $this->assertNotNull($business->profile_image);
        $this->assertStringContainsString('profile-images/', $business->profile_image);
        $this->assertTrue(Storage::disk('public')->exists($business->profile_image));

        $imageUrl = route('profile.image', ['filename' => basename($business->profile_image)]);
        $this->get($imageUrl)->assertOk();
    }
}
