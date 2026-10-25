<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Administrator;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('web')->check()) {
            return redirect()->route('business.dashboard');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'identifier.required' => 'Enter your username or business email.',
            'password.required' => 'Enter your password.',
        ]);

        $identifier = trim($credentials['identifier']);
        $remember = $request->boolean('remember');
        $administrator = Administrator::query()
            ->where('email', mb_strtolower($identifier))
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($identifier)])
            ->first();

        if ($administrator && Hash::check($credentials['password'], $administrator->password)) {
            Auth::guard('web')->logout();
            Auth::guard('admin')->login($administrator, $remember);
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        Auth::guard('admin')->logout();

        if (! Auth::guard('web')->attempt([
            'email' => mb_strtolower($identifier),
            'password' => $credentials['password'],
        ], $remember)) {
            return back()
                ->withErrors(['identifier' => 'Those sign-in details do not match an EcoCycle account.'])
                ->onlyInput('identifier');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('business.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been signed out.');
    }
}
