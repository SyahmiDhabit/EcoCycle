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
}
