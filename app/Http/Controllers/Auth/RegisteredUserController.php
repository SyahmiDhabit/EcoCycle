<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\User;
use App\Services\BusinessMailNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
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
        $cert = $request->file('ssm_certificate');
        $prof = $request->file('ssm_profile');

        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_registration_number' => ['required', 'string', 'max:60', 'unique:business,business_registration_number'],
            'industry' => ['required', 'in:Agriculture & food,Construction & building materials,Furniture & wood products,Manufacturing,Packaging & paper,Textiles & apparel,Other'],
            'business_phone' => ['required', 'string', 'max:30'],
            'registered_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:12'],
            'state' => ['required', 'in:Johor,Kedah,Kelantan,Kuala Lumpur,Labuan,Melaka,Negeri Sembilan,Pahang,Penang,Perak,Perlis,Putrajaya,Sabah,Sarawak,Selangor,Terengganu'],
            'name' => ['required', 'string', 'max:255', 'unique:business,name'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:business,email'],
            'password' => ['required', 'confirmed', Password::min(8)->numbers()],
            'password_confirmation' => ['required', Password::min(8)->numbers()],
            'profile_image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'ssm_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'ssm_profile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
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
            'name.required' => 'Enter your company username.',
            'name.unique' => 'This company username is already in use. You cannot use this username.',
            'email.required' => 'Enter your business email address.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'This business email is already in use. You cannot use this email.',
            'password.required' => 'Enter a password.',
            'password.min' => 'At least 8 characters, including a number.',
            'password.numbers' => 'At least 8 characters, including a number.',
            'password.confirmed' => 'The passwords do not match.',
            'password_confirmation.required' => 'Confirm your password.',
            'password_confirmation.min' => 'At least 8 characters, including a number.',
            'password_confirmation.numbers' => 'At least 8 characters, including a number.',
            'ssm_certificate.required' => 'Upload your SSM registration certificate.',
            'ssm_certificate.mimes' => 'The certificate must be a PDF, JPG, PNG, or WEBP file.',
            'ssm_certificate.max' => 'The certificate must not exceed 10 MB.',
            'ssm_certificate.uploaded' => $this->getUploadErrorMessage($cert, 'The SSM certificate failed to upload. Please ensure the file is under 10 MB and in PDF, JPG, PNG, or WEBP format.'),
            'ssm_profile.required' => 'Upload your current SSM company or business profile.',
            'ssm_profile.mimes' => 'The SSM profile must be a PDF, JPG, PNG, or WEBP file.',
            'ssm_profile.max' => 'The SSM profile must not exceed 10 MB.',
            'ssm_profile.uploaded' => $this->getUploadErrorMessage($prof, 'The SSM profile failed to upload. Please ensure the file is under 10 MB and in PDF, JPG, PNG, or WEBP format.'),
            'profile_image.required' => 'Upload your company profile image or business logo.',
            'profile_image.image' => 'The profile image must be a valid image file.',
            'profile_image.mimes' => 'The profile image must be a JPG, JPEG, PNG, or WEBP file.',
            'profile_image.max' => 'The profile image must not exceed 5 MB.',
            'profile_image.uploaded' => $this->getUploadErrorMessage($request->file('profile_image'), 'The profile image failed to upload. Please ensure the file is under 5 MB and in JPG, JPEG, PNG, or WEBP format.'),
        ]);

        $certificatePath = null;
        $profilePath = null;
        $profileImagePath = null;

        try {
            $certificatePath = $request->file('ssm_certificate')->store('business-verification', 'local');
            $profilePath = $request->file('ssm_profile')->store('business-verification', 'local');

            if ($request->hasFile('profile_image')) {
                $profileImagePath = $request->file('profile_image')->store('profile-images', 'public');
            }

            DB::transaction(function () use ($validated, $certificatePath, $profilePath, $profileImagePath): void {
                User::create([
                    'business_name' => $validated['business_name'],
                    'business_registration_number' => $validated['business_registration_number'],
                    'industry' => $validated['industry'],
                    'business_phone' => $validated['business_phone'],
                    'registered_address' => $validated['registered_address'],
                    'city' => $validated['city'],
                    'postcode' => $validated['postcode'],
                    'state' => $validated['state'],
                    'profile_image' => $profileImagePath,
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
            if ($profileImagePath) {
                Storage::disk('public')->delete($profileImagePath);
            }
            report($exception);

            return back()->withInput()->withErrors([
                'business_name' => 'An error occurred while creating your account. Please try again.',
            ]);
        }

        $business = User::query()->where('email', $validated['email'])->firstOrFail();
        app(BusinessMailNotifier::class)->submitted($business);

        AdminNotification::send(
            'new_registration',
            'New Business Registration',
            "{$business->business_name} ({$business->business_registration_number}) has submitted registration documents for review.",
            '/admin/dashboard',
            $business->id
        );

        Auth::guard('admin')->logout();
        Auth::guard('web')->login($business);
        $request->session()->regenerate();

        return redirect()->route('business.pending')->with(
            'status',
            'Your business application has been submitted and is pending administrator review.',
        );
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $field = (string) $request->query('field');
        $value = trim((string) $request->query('value'));

        if (! in_array($field, ['name', 'email'], true) || $value === '') {
            return response()->json(['available' => true]);
        }

        if ($field === 'email') {
            $value = mb_strtolower($value);
        }

        $exists = User::query()->where($field, $value)->exists();

        return response()->json([
            'available' => ! $exists,
            'field' => $field,
            'message' => $exists
                ? ($field === 'name'
                    ? 'This company username is already in use. You cannot use this username.'
                    : 'This business email is already in use. You cannot use this email.')
                : null,
        ]);
    }

    private function getUploadErrorMessage(?UploadedFile $file, string $defaultMessage): string
    {
        if (! $file) {
            return $defaultMessage;
        }

        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE => 'The file exceeds the PHP server upload limit (upload_max_filesize). Please restart your php artisan serve terminal to apply the 32MB limit, or upload a file under 2 MB.',
            UPLOAD_ERR_FORM_SIZE => 'The file exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try submitting again.',
            UPLOAD_ERR_NO_FILE => 'No file was received. Please re-select your document before submitting.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server error: Temporary upload directory is missing on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Server error: Failed to write uploaded file to disk. Check permissions on temp folder.',
            UPLOAD_ERR_EXTENSION => 'Server error: A PHP extension stopped the file upload.',
            default => $defaultMessage,
        };
    }
}
