<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_registration_number' => ['required', 'string', 'max:60', 'unique:users,business_registration_number'],
            'industry' => ['required', 'in:Agriculture & food,Construction & building materials,Furniture & wood products,Manufacturing,Packaging & paper,Textiles & apparel,Other'],
            'business_phone' => ['required', 'string', 'max:30'],
            'registered_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:12'],
            'state' => ['required', 'in:Johor,Kedah,Kelantan,Kuala Lumpur,Labuan,Melaka,Negeri Sembilan,Pahang,Penang,Perak,Perlis,Putrajaya,Sabah,Sarawak,Selangor,Terengganu'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->numbers()],
            'ssm_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'ssm_profile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'terms_accepted' => ['accepted'],
        ], [
            'business_name.required' => 'Enter your business name.',
            'business_registration_number.required' => 'Enter the SSM registration number shown on your documents.',
            'business_registration_number.unique' => 'An account with this SSM registration number already exists.',
            'industry.required' => 'Choose the industry that best describes your business.',
            'industry.in' => 'Choose an industry from the list.',
            'business_phone.required' => 'Enter a phone number where we can reach your business.',
            'registered_address.required' => 'Enter the registered address shown on your SSM documents.',
            'city.required' => 'Enter the registered city.',
            'postcode.required' => 'Enter the registered postcode.',
            'state.required' => 'Choose the registered state.',
            'state.in' => 'Choose a state from the list.',
            'name.required' => 'Enter the business owner name.',
            'email.required' => 'Enter your business email address.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'An account with this email already exists. Try signing in instead.',
            'password.confirmed' => 'The passwords do not match.',
            'password.numbers' => 'Include at least one number in your password.',
            'ssm_certificate.required' => 'Upload your SSM registration certificate.',
            'ssm_certificate.mimes' => 'The certificate must be a PDF, JPG, or PNG file.',
            'ssm_certificate.max' => 'The certificate must not exceed 10 MB.',
            'ssm_profile.required' => 'Upload your current SSM company or business profile.',
            'ssm_profile.mimes' => 'The SSM profile must be a PDF, JPG, or PNG file.',
            'ssm_profile.max' => 'The SSM profile must not exceed 10 MB.',
            'terms_accepted.accepted' => 'Confirm that you are authorised to represent this business.',
        ]);

        $certificatePath = null;
        $profilePath = null;

        try {
            $certificatePath = $request->file('ssm_certificate')->store('business-verification', 'local');
            $profilePath = $request->file('ssm_profile')->store('business-verification', 'local');

            DB::transaction(function () use ($validated, $certificatePath, $profilePath): void {
                User::create([
                    'business_name' => $validated['business_name'],
                    'business_registration_number' => $validated['business_registration_number'],
                    'industry' => $validated['industry'],
                    'business_phone' => $validated['business_phone'],
                    'registered_address' => $validated['registered_address'],
                    'city' => $validated['city'],
                    'postcode' => $validated['postcode'],
                    'state' => $validated['state'],
                    'ssm_certificate_path' => $certificatePath,
                    'ssm_profile_path' => $profilePath,
                    'terms_accepted_at' => now(),
                    'account_status' => 'pending',
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_filter([$certificatePath, $profilePath]));
            throw $exception;
        }

        return redirect()->route('register')->with(
            'status',
            'Your business application has been submitted and is pending administrator review.'
        );
    }
}
