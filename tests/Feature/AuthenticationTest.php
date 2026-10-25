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
            ->assertSee('Keep me signed in')
            ->assertSee('Sign in to workspace')
            ->assertSee('Business email or admin username')
            ->assertSee('name="identifier"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('/images/figma/ecocycle-leaf.svg')
            ->assertSee('name="_token"', false)
            ->assertDontSee('visual preview');
    }

    public function test_valid_credentials_sign_in_and_redirect_to_the_business_workspace(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $this->post(route('login.store'), [
            'identifier' => ' OWNER@EXAMPLE.COM ',
            'password' => 'Password123',
        ])
            ->assertRedirect(route('business.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('business.dashboard'))->assertOk();
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

    public function test_remember_me_sets_a_persistent_remember_token(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('Password123'),
            'remember_token' => null,
        ]);

        $this->post(route('login.store'), [
            'identifier' => 'owner@example.com',
            'password' => 'Password123',
            'remember' => '1',
        ])->assertRedirect(route('business.dashboard'));

        $this->assertNotNull($user->fresh()->remember_token);
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
