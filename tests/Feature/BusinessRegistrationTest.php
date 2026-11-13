<?php

namespace Tests\Feature;

use App\Mail\BusinessApplicationSubmitted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_registration_page_shows_the_business_signup_form_and_login_link(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Registered business name')
            ->assertSee('Company username')
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
            'profile_image' => UploadedFile::fake()->image('logo.png'),
            'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            'ssm_profile' => UploadedFile::fake()->image('profile.png'),
        ]);

        $response->assertRedirect(route('business.dashboard'))
            ->assertSessionHas('status');

        $user = User::where('email', 'amir@kayukita.com.my')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('business', [
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

        $this->assertTrue(Hash::check('EcoCyclePass123', $user->password));
        $this->assertNotSame('EcoCyclePass123', $user->password);
        $this->assertTrue(Storage::disk('local')->exists($user->ssm_certificate_path));
        $this->assertTrue(Storage::disk('local')->exists($user->ssm_profile_path));

        $this->assertDatabaseHas('admin_notification', [
            'type' => 'new_registration',
            'business_id' => $user->id,
            'is_read' => false,
        ]);

        $this->get(route('business.dashboard'))
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
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['business_name', 'business_registration_number', 'industry', 'business_phone', 'registered_address', 'city', 'postcode', 'state', 'name', 'email', 'password', 'profile_image', 'ssm_certificate', 'ssm_profile']);

        $this->assertDatabaseCount('business', 0);
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
                'profile_image' => UploadedFile::fake()->image('logo.png'),
                'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
                'ssm_profile' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('business', 1);
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
                'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
                'ssm_profile' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['password']);

        $this->assertDatabaseCount('business', 0);
    }

    public function test_registration_sends_email_notification_to_admin(): void
    {
        Mail::fake();

        $response = $this->post(route('register.store'), [
            'business_name' => 'Eco Ventures',
            'business_registration_number' => '202601009988 (1999999-Z)',
            'industry' => 'Manufacturing',
            'business_phone' => '+60 12-999 8888',
            'registered_address' => '22 Jalan Hijau',
            'city' => 'Kuala Lumpur',
            'postcode' => '50000',
            'state' => 'Kuala Lumpur',
            'name' => 'ecoventures_user',
            'email' => 'ventures@ecoventures.com',
            'password' => 'SecurePass999',
            'password_confirmation' => 'SecurePass999',
            'profile_image' => UploadedFile::fake()->image('logo.png'),
            'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            'ssm_profile' => UploadedFile::fake()->image('profile.png'),
        ]);

        $response->assertRedirect(route('business.dashboard'));

        Mail::assertSent(BusinessApplicationSubmitted::class, function ($mail) {
            return $mail->hasTo(config('services.ecocycle.admin_email'));
        });
    }

    public function test_registration_rejects_an_existing_username(): void
    {
        User::factory()->create(['name' => 'existing_username']);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'business_name' => 'Unique Business',
                'business_registration_number' => '202101023999 (1425699-Y)',
                'industry' => 'Packaging & paper',
                'business_phone' => '+60 3-5510 2488',
                'registered_address' => '12 Jalan Kertas',
                'city' => 'Klang',
                'postcode' => '41200',
                'state' => 'Selangor',
                'name' => 'existing_username',
                'email' => 'different@email.com',
                'password' => 'EcoCyclePass123',
                'password_confirmation' => 'EcoCyclePass123',
                'profile_image' => UploadedFile::fake()->image('logo.png'),
                'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
                'ssm_profile' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['name']);

        $this->assertEquals(
            'This company username is already in use. You cannot use this username.',
            session('errors')->first('name')
        );
    }

    public function test_password_and_confirmation_must_be_at_least_8_chars_and_include_a_number(): void
    {
        // Missing number
        $this->from(route('register'))
            ->post(route('register.store'), [
                'business_name' => 'Alpha Corp',
                'business_registration_number' => '202101023777 (1425677-B)',
                'industry' => 'Packaging & paper',
                'business_phone' => '+60 3-5510 2488',
                'registered_address' => '12 Jalan Kertas',
                'city' => 'Klang',
                'postcode' => '41200',
                'state' => 'Selangor',
                'name' => 'alphacorp',
                'email' => 'alpha@corp.com',
                'password' => 'NoNumberPassword',
                'password_confirmation' => 'NoNumberPassword',
                'profile_image' => UploadedFile::fake()->image('logo.png'),
                'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
                'ssm_profile' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['password']);

        // Less than 8 characters
        $this->from(route('register'))
            ->post(route('register.store'), [
                'business_name' => 'Alpha Corp',
                'business_registration_number' => '202101023777 (1425677-B)',
                'industry' => 'Packaging & paper',
                'business_phone' => '+60 3-5510 2488',
                'registered_address' => '12 Jalan Kertas',
                'city' => 'Klang',
                'postcode' => '41200',
                'state' => 'Selangor',
                'name' => 'alphacorp',
                'email' => 'alpha@corp.com',
                'password' => 'Pass1',
                'password_confirmation' => 'Pass1',
                'profile_image' => UploadedFile::fake()->image('logo.png'),
                'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
                'ssm_profile' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors(['password']);
    }

    public function test_check_availability_endpoint(): void
    {
        User::factory()->create([
            'name' => 'taken_username',
            'email' => 'taken@business.com',
        ]);

        $this->getJson(route('register.check', ['field' => 'name', 'value' => 'taken_username']))
            ->assertOk()
            ->assertJson([
                'available' => false,
                'field' => 'name',
                'message' => 'This company username is already in use. You cannot use this username.',
            ]);

        $this->getJson(route('register.check', ['field' => 'name', 'value' => 'free_username']))
            ->assertOk()
            ->assertJson([
                'available' => true,
                'field' => 'name',
                'message' => null,
            ]);

        $this->getJson(route('register.check', ['field' => 'email', 'value' => 'TAKEN@BUSINESS.COM']))
            ->assertOk()
            ->assertJson([
                'available' => false,
                'field' => 'email',
                'message' => 'This business email is already in use. You cannot use this email.',
            ]);

        $this->getJson(route('register.check', ['field' => 'email', 'value' => 'free@business.com']))
            ->assertOk()
            ->assertJson([
                'available' => true,
                'field' => 'email',
                'message' => null,
            ]);
    }

    public function test_business_registration_with_profile_image(): void
    {
        $response = $this->post(route('register.store'), [
            'business_name' => 'Eco Timber Sdn Bhd',
            'business_registration_number' => '202201099887 (1499999-T)',
            'industry' => 'Furniture & wood products',
            'business_phone' => '+60 12-998 8776',
            'registered_address' => '22 Jalan Perindustrian 4',
            'city' => 'Klang',
            'postcode' => '41050',
            'state' => 'Selangor',
            'name' => 'ecotimber_admin',
            'email' => 'admin@ecotimber.com.my',
            'password' => 'EcoCyclePass123',
            'password_confirmation' => 'EcoCyclePass123',
            'profile_image' => UploadedFile::fake()->image('company_logo.png', 200, 200),
            'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
            'ssm_profile' => UploadedFile::fake()->image('profile.png'),
        ]);

        $response->assertRedirect(route('business.dashboard'));

        $user = User::where('email', 'admin@ecotimber.com.my')->firstOrFail();
        $this->assertNotNull($user->profile_image);
        $this->assertStringContainsString('profile-images/', $user->profile_image);
        $this->assertTrue(Storage::disk('public')->exists($user->profile_image));
        $this->assertNotNull($user->profile_image_url);
    }

    public function test_profile_image_is_required_for_registration(): void
    {
        $response = $this->from(route('register'))
            ->post(route('register.store'), [
                'business_name' => 'Eco Timber Sdn Bhd',
                'business_registration_number' => '202201099887 (1499999-T)',
                'industry' => 'Furniture & wood products',
                'business_phone' => '+60 12-998 8776',
                'registered_address' => '22 Jalan Perindustrian 4',
                'city' => 'Klang',
                'postcode' => '41050',
                'state' => 'Selangor',
                'name' => 'ecotimber_admin',
                'email' => 'admin@ecotimber.com.my',
                'password' => 'EcoCyclePass123',
                'password_confirmation' => 'EcoCyclePass123',
                'ssm_certificate' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
                'ssm_profile' => UploadedFile::fake()->image('profile.png'),
            ]);

        $response->assertRedirect(route('register'))
            ->assertSessionHasErrors(['profile_image']);

        $this->assertEquals(
            'Upload your company profile image or business logo.',
            session('errors')->first('profile_image')
        );
    }
}
