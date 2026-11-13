<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_page_matches_ecocycle_brand_and_has_a_csrf_protected_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Welcome back.')
            ->assertSee('Sign in to workspace')
            ->assertSee('Business email')
            ->assertSee('name="identifier"', false)
            ->assertSee('/images/figma/ecocycle-leaf.svg')
            ->assertSee('name="_token"', false)
            ->assertDontSee('visual preview');
    }

    public function test_valid_credentials_sign_in_and_redirect_to_the_business_workspace(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('Password123'),
            'account_status' => 'approved',
        ]);

        $this->post(route('login.store'), [
            'identifier' => ' OWNER@EXAMPLE.COM ',
            'password' => 'Password123',
        ])
            ->assertRedirect(route('business.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('business.dashboard'))->assertOk();
    }

    public function test_unapproved_business_signs_in_and_redirects_to_pending_approval_page(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'password' => Hash::make('Password123'),
            'account_status' => 'pending',
        ]);

        $this->post(route('login.store'), [
            'identifier' => 'pending@example.com',
            'password' => 'Password123',
        ])
            ->assertRedirect(route('business.pending'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('business.pending'))
            ->assertOk()
            ->assertSee('Waiting for Admin Approval');

        // Blocked from accessing the dashboard directly
        $this->get(route('business.dashboard'))
            ->assertRedirect(route('business.pending'));
    }

    public function test_business_can_sign_in_with_company_username(): void
    {
        $user = User::factory()->create([
            'name' => 'kayukita_enterprise',
            'email' => 'owner@kayukita.com.my',
            'password' => Hash::make('Password123'),
            'account_status' => 'approved',
        ]);

        $this->post(route('login.store'), [
            'identifier' => ' KAYUKITA_ENTERPRISE ',
            'password' => 'Password123',
        ])
            ->assertRedirect(route('business.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_show_a_safe_error_without_preserving_the_password(): void
    {
        User::factory()->create(['email' => 'owner@example.com']);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'identifier' => 'owner@example.com',
                'password' => 'not-the-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('identifier')
            ->assertSessionHas('_old_input.identifier', 'owner@example.com')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_sign_in_works_without_remember_token(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('Password123'),
            'account_status' => 'approved',
        ]);

        $this->post(route('login.store'), [
            'identifier' => 'owner@example.com',
            'password' => 'Password123',
        ])->assertRedirect(route('business.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_sign_out_invalidates_the_authenticated_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'You have been signed out.');

        $this->assertGuest();
    }

    public function test_business_workspace_requires_authentication(): void
    {
        $this->get(route('business.dashboard'))
            ->assertRedirect(route('login'));
    }
}
