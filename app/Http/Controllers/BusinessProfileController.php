<?php

namespace App\Http\Controllers;

use App\Models\BusinessNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class BusinessProfileController extends Controller
{
    private function checkApproved(): void
    {
        abort_unless(Auth::user()->account_status === 'approved', 403, 'Your business application must be approved first.');
    }

    public function show(): View
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        return view('business.profile.index', [
            'business' => $business,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'industry' => ['required', 'string', 'max:100'],
            'business_phone' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'registered_address' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:20'],
            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            if ($file && $file->isValid()) {
                $ext = $file->getClientOriginalExtension() ?: 'png';
                $filename = 'profile_' . Str::random(24) . '.' . $ext;
                Storage::disk('public')->putFileAs('profile-images', $file, $filename);
                $business->profile_image = '/profile-images/' . $filename;
            }
        }

        $business->business_name = $validated['business_name'];
        $business->business_registration_number = $validated['business_registration_number'] ?? ($validated['registration_number'] ?? $business->business_registration_number);
        $business->industry = $validated['industry'];
        $business->business_phone = $validated['business_phone'] ?? ($validated['phone'] ?? $business->business_phone);
        $business->registered_address = $validated['registered_address'] ?? ($validated['address'] ?? $business->registered_address);
        $business->city = $validated['city'];
        $business->state = $validated['state'];
        $business->postcode = $validated['postcode'];
        $business->save();

        BusinessNotification::send(
            $business->id,
            'Profile Updated',
            'Your business profile details and branding have been updated.',
            '/business/profile'
        );

        return back()->with('status', 'Business profile information updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $this->checkApproved();
        /** @var User $business */
        $business = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $business->password)) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        $business->password = Hash::make($validated['password']);
        $business->save();

        BusinessNotification::send(
            $business->id,
            'Security Alert: Password Changed',
            'Your business account password was recently changed. If you did not make this change, please contact support immediately.',
            '/business/profile'
        );

        return back()->with('status', 'Password changed successfully.');
    }
}
