<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetLink;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = mb_strtolower(trim($validated['email']));
        $broker = Administrator::query()->where('email', $email)->exists() ? 'administrator' : 'business';

        try {
            $status = Password::broker($broker)->sendResetLink(
                ['email' => $email],
                function ($user, string $token) use ($email, $broker): void {
                    $url = route('password.reset', [
                        'token' => $token,
                        'email' => $email,
                        'broker' => $broker,
                    ]);

                    Mail::to($email)->send(new PasswordResetLink(
                        $user->name,
                        $url,
                        app()->getLocale(),
                    ));
                },
            );
        } catch (Throwable $exception) {
            Log::error('EcoCycle password reset email could not be sent.', [
                'exception' => $exception::class,
            ]);

            return back()->withErrors([
                'email' => 'We could not send the reset email right now. Please try again later.',
            ])->onlyInput('email');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Please wait a minute before requesting another reset link.',
            ])->onlyInput('email');
        }

        return back()->with('status', 'If that email belongs to an EcoCycle account, a password reset link is on its way.');
    }

    public function edit(Request $request, string $token): View
    {
        $broker = $request->query('broker', 'business');
        abort_unless(in_array($broker, ['business', 'administrator'], true), 404);

        return view('auth.reset-password', [
            'token' => $token,
            'email' => mb_strtolower((string) $request->query('email')),
            'broker' => $broker,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'broker' => ['required', 'in:business,administrator'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->numbers()],
        ]);

        $status = Password::broker($validated['broker'])->reset(
            [
                'email' => mb_strtolower($validated['email']),
                'token' => $validated['token'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => 'This reset link is invalid or expired. Request a new one and try again.',
            ])->withInput($request->only('email'));
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. You can sign in now.');
    }
}
