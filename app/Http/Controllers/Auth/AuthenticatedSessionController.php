<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $administrator = Administrator::query()
            ->where('email', mb_strtolower($identifier))
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($identifier)])
            ->first();

        if ($administrator && Hash::check($credentials['password'], $administrator->password)) {
            Auth::guard('web')->logout();
            Auth::guard('admin')->login($administrator);
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        Auth::guard('admin')->logout();

        $business = User::query()
            ->where('email', mb_strtolower($identifier))
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($identifier)])
            ->first();

        $remember = $request->boolean('remember');

        if ($business && Hash::check($credentials['password'], $business->password)) {
            Auth::guard('web')->login($business, $remember);
            $request->session()->regenerate();

            if ($business->account_status === 'approved') {
                return redirect()->intended(route('business.dashboard'));
            }

            return redirect()->route('business.pending');
        }

        return back()
            ->withErrors(['identifier' => 'Those sign-in details do not match an EcoCycle account.'])
            ->onlyInput('identifier');
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
