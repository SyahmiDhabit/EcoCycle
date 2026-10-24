<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_registration_page_shows_the_business_signup_form_and_login_link(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Registered business name')
            ->assertSee('Contact person')
            ->assertSee('Business email')
            ->assertSee('Confirm password')
            ->assertSee('SSM registration certificate')
            ->assertSee('ecocycle-leaf.svg')
            ->assertSee('name="_token"', false);
    }

    public function test_business_registration_saves_a_hashed_password_and_redirects_to_login(): void
    {
        $response = $this->post(route('register.store'), [
            'business_name' => 'Kayu Kita',
            'business_registration_number' => '202101023456 (1425678-X)',
            'industry' => 'Furniture & wood products',
            'business_phone' => '+60 3-5510 2488',
            'registered_address' => '18 Jalan Industri U6/8',
            'city' => 'Shah Alam',
            'postcode' => '40150',
            'state' => 'Selangor',
            'name' => 'Amir Rahman',
            'email' => 'AMIR@KAYUKITA.COM.MY',
            'password' => 'EcoCyclePass123',
            'password_confirmation' => 'EcoCyclePass123',
            'terms_accepted' => '1',
            'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            'ssm_profile' => UploadedFile::fake()->image('profile.png'),
        ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHas('status');

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'business_name' => 'Kayu Kita',
            'business_registration_number' => '202101023456 (1425678-X)',
            'industry' => 'Furniture & wood products',
            'business_phone' => '+60 3-5510 2488',
            'registered_address' => '18 Jalan Industri U6/8',
            'city' => 'Shah Alam',
            'postcode' => '40150',
            'state' => 'Selangor',
            'name' => 'Amir Rahman',
            'email' => 'amir@kayukita.com.my',
            'account_status' => 'pending',
        ]);

        $user = User::where('email', 'amir@kayukita.com.my')->firstOrFail();
        $this->assertTrue(Hash::check('EcoCyclePass123', $user->password));
        $this->assertNotSame('EcoCyclePass123', $user->password);
        Storage::disk('local')->assertExists($user->ssm_certificate_path);
        Storage::disk('local')->assertExists($user->ssm_profile_path);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Your business application has been submitted and is pending administrator review.');
    }

    public function test_registration_rejects_missing_and_invalid_fields(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'business_name' => '',
            'business_registration_number' => '',
            'industry' => '',
            'business_phone' => '',
            'registered_address' => '',
            'city' => '',
            'postcode' => '',
            'state' => '',
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
                'password_confirmation' => 'different',
            'terms_accepted' => '',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['business_name', 'business_registration_number', 'industry', 'business_phone', 'registered_address', 'city', 'postcode', 'state', 'name', 'email', 'password', 'ssm_certificate', 'ssm_profile', 'terms_accepted']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_an_existing_email_even_when_case_differs(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'business_name' => 'Another Business',
                'business_registration_number' => '202101023457 (1425679-X)',
                'industry' => 'Manufacturing',
                'business_phone' => '+60 3-5510 2488',
                'registered_address' => 'Some address',
                'city' => 'Shah Alam',
                'postcode' => '40150',
                'state' => 'Selangor',
                'name' => 'Business Owner',
                'email' => 'OWNER@EXAMPLE.COM',
                'password' => 'EcoCyclePass123',
                'password_confirmation' => 'EcoCyclePass123',
                'terms_accepted' => '1',
                'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
                'ssm_profile' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_requires_a_matching_password_confirmation(): void
    {
        $this->from(route('register'))
            ->post(route('register.store'), [
                'business_name' => 'Kayu Kita',
                'business_registration_number' => '202101023456 (1425678-X)',
                'industry' => 'Furniture & wood products',
                'business_phone' => '+60 3-5510 2488',
                'registered_address' => '18 Jalan Industri U6/8',
                'city' => 'Shah Alam',
                'postcode' => '40150',
                'state' => 'Selangor',
                'name' => 'Amir Rahman',
                'email' => 'amir@example.com',
                'password' => 'EcoCyclePass123',
                'password_confirmation' => 'NotTheSame123',
                'terms_accepted' => '1',
                'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
                'ssm_profile' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }
}
