<?php

namespace Tests\Feature;

use App\Mail\PasswordResetLink;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk()
            ->assertSee('Forgot password?')
            ->assertSee('Send reset link')
            ->assertSee('signin-login')
            ->assertSee('Back to sign in');
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'business@example.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'business@example.com',
        ]);

        $response->assertSessionHas('status');
        Mail::assertSent(PasswordResetLink::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        /** @var PasswordBroker $broker */
        $broker = Password::broker('business');
        $token = $broker->createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
            'broker' => 'business',
        ]));

        $response->assertOk()
            ->assertSee('Set new password')
            ->assertSee('Save new password')
            ->assertSee('signin-login')
            ->assertSee('Back to sign in');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        /** @var PasswordBroker $broker */
        $broker = Password::broker('business');
        $token = $broker->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'broker' => 'business',
            'password' => 'NewPassword999',
            'password_confirmation' => 'NewPassword999',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPassword999', $user->fresh()->password));
    }
}
