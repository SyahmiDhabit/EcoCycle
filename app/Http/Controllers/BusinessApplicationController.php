<?php

namespace App\Http\Controllers;

<<<<<<< Updated upstream
=======
use App\Models\AdminNotification;
use App\Models\User;
>>>>>>> Stashed changes
use App\Services\BusinessMailNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessApplicationController extends Controller
{
    public function show(Request $request): View
    {
<<<<<<< Updated upstream
=======
        /** @var User $business */
        $business = Auth::guard('web')->user();

>>>>>>> Stashed changes
        return view('business.verification', [
            'business' => $request->user(),
        ]);
    }

    public function update(Request $request, BusinessMailNotifier $mailer): RedirectResponse
    {
<<<<<<< Updated upstream
        $business = $request->user();
=======
        /** @var User $business */
        $business = Auth::guard('web')->user();
>>>>>>> Stashed changes

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_registration_number' => [
                'required',
                'string',
                'max:60',
                Rule::unique('business', 'business_registration_number')->ignore($business->id),
            ],
            'industry' => [
                'required',
                'in:Agriculture & food,Construction & building materials,Furniture & wood products,Manufacturing,Packaging & paper,Textiles & apparel,Other',
            ],
            'business_phone' => ['required', 'string', 'max:30'],
            'registered_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postcode' => ['required', 'string', 'max:12'],
            'state' => [
                'required',
                'in:Johor,Kedah,Kelantan,Kuala Lumpur,Labuan,Melaka,Negeri Sembilan,Pahang,Penang,Perak,Perlis,Putrajaya,Sabah,Sarawak,Selangor,Terengganu',
            ],
            'name' => ['required', 'string', 'max:255'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'ssm_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'ssm_profile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'business_name.required' => 'Enter your business name.',
            'business_registration_number.required' => 'Enter the SSM registration number shown on your documents.',
            'business_registration_number.unique' => 'An account with this SSM registration number already exists.',
            'industry.required' => 'Choose your industry.',
            'business_phone.required' => 'Enter your business phone number.',
            'registered_address.required' => 'Enter your registered address.',
            'city.required' => 'Enter the registered city.',
            'postcode.required' => 'Enter the registered postcode.',
            'state.required' => 'Choose your registered state.',
            'name.required' => 'Enter the contact person name.',
            'profile_image.image' => 'The profile image must be a valid image file.',
            'profile_image.mimes' => 'The profile image must be a JPG, JPEG, PNG, or WEBP file.',
            'profile_image.max' => 'The profile image must not exceed 5 MB.',
            'ssm_certificate.mimes' => 'The certificate must be a PDF, JPG, or PNG file.',
            'ssm_certificate.max' => 'The certificate must not exceed 10 MB.',
            'ssm_certificate.uploaded' => $this->getUploadErrorMessage($request->file('ssm_certificate'), 'The SSM certificate failed to upload. Please ensure the file is under 10 MB and in PDF, JPG, or PNG format.'),
            'ssm_profile.mimes' => 'The SSM profile must be a PDF, JPG, or PNG file.',
            'ssm_profile.max' => 'The SSM profile must not exceed 10 MB.',
            'ssm_profile.uploaded' => $this->getUploadErrorMessage($request->file('ssm_profile'), 'The SSM profile failed to upload. Please ensure the file is under 10 MB and in PDF, JPG, or PNG format.'),
        ]);

        $newCertificatePath = null;
        $newProfilePath = null;
        $newProfileImagePath = null;
        $oldCertificatePath = $business->ssm_certificate_path;
        $oldProfilePath = $business->ssm_profile_path;
        $oldProfileImagePath = $business->profile_image;

        try {
            if ($request->hasFile('profile_image')) {
                $newProfileImagePath = $request->file('profile_image')->store('profile-images', 'public');
            }

            if ($request->hasFile('ssm_certificate')) {
                $newCertificatePath = $request->file('ssm_certificate')->store('business-verification', 'local');
            }

            if ($request->hasFile('ssm_profile')) {
                $newProfilePath = $request->file('ssm_profile')->store('business-verification', 'local');
            }

            $updateData = [
                'business_name' => $validated['business_name'],
                'business_registration_number' => $validated['business_registration_number'],
                'industry' => $validated['industry'],
                'business_phone' => $validated['business_phone'],
                'registered_address' => $validated['registered_address'],
                'city' => $validated['city'],
                'postcode' => $validated['postcode'],
                'state' => $validated['state'],
                'name' => $validated['name'],
            ];

            if ($newProfileImagePath) {
                $updateData['profile_image'] = $newProfileImagePath;
            }

            if ($newCertificatePath) {
                $updateData['ssm_certificate_path'] = $newCertificatePath;
            }

            if ($newProfilePath) {
                $updateData['ssm_profile_path'] = $newProfilePath;
            }

            if ($business->account_status === 'rejected') {
                $updateData['account_status'] = 'pending';
                $updateData['review_note'] = null;
            }

            $business->update($updateData);

            if ($newProfileImagePath && $oldProfileImagePath) {
                Storage::disk('public')->delete($oldProfileImagePath);
            }

            if ($newCertificatePath && $oldCertificatePath) {
                Storage::disk('local')->delete($oldCertificatePath);
            }

            if ($newProfilePath && $oldProfilePath) {
                Storage::disk('local')->delete($oldProfilePath);
            }
        } catch (\Throwable $exception) {
            if ($newProfileImagePath) {
                Storage::disk('public')->delete($newProfileImagePath);
            }
            if ($newCertificatePath) {
                Storage::disk('local')->delete($newCertificatePath);
            }
            if ($newProfilePath) {
                Storage::disk('local')->delete($newProfilePath);
            }
            throw $exception;
        }

        $mailer->submitted($business->fresh(), isUpdate: true);

        AdminNotification::send(
            'application_resubmitted',
            'Application Resubmitted',
            "{$business->business_name} has updated their registration information and documents for administrator review.",
            '/admin',
            $business->id
        );

        return back()->with(
            'status',
            'Your application details have been updated successfully and submitted for administrator review.',
        );
    }

    public function document(Request $request, string $document): StreamedResponse
    {
        abort_unless(in_array($document, ['certificate', 'profile'], true), 404);

<<<<<<< Updated upstream
        $business = $request->user();
=======
        /** @var User $business */
        $business = Auth::guard('web')->user();
>>>>>>> Stashed changes
        $path = $document === 'certificate' ? $business->ssm_certificate_path : $business->ssm_profile_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

<<<<<<< Updated upstream
        return Storage::disk('local')->download($path);
=======
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->download($path);
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $business */
        $business = Auth::guard('web')->user();

        if ($business->ssm_certificate_path) {
            Storage::disk('local')->delete($business->ssm_certificate_path);
        }

        if ($business->ssm_profile_path) {
            Storage::disk('local')->delete($business->ssm_profile_path);
        }

        if ($business->profile_image) {
            Storage::disk('public')->delete($business->profile_image);
        }

        Auth::guard('web')->logout();
        $business->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('register')->with(
            'status',
            'Your application data and uploaded documents have been completely removed. You can now register again.'
        );
>>>>>>> Stashed changes
    }

    public function resubmit(Request $request, BusinessMailNotifier $mailer): RedirectResponse
    {
        $business = $request->user();
        abort_unless($business->account_status === 'rejected', 404);

        $validated = $request->validate([
            'ssm_profile' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'ssm_profile.required' => 'Choose the updated SSM company profile.',
            'ssm_profile.mimes' => 'The profile must be a PDF, JPG, or PNG file.',
            'ssm_profile.max' => 'The profile must not exceed 10 MB.',
        ]);

        $oldPath = $business->ssm_profile_path;
        $newPath = $validated['ssm_profile']->store('business-verification', 'local');

        try {
            $business->update([
                'ssm_profile_path' => $newPath,
                'account_status' => 'pending',
                'review_note' => null,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        $mailer->submitted($business->fresh(), isUpdate: true);

        return redirect()->route('business.dashboard')->with('status', 'Your updated SSM profile has been resubmitted for administrator review.');
    }

    private function getUploadErrorMessage(?\Illuminate\Http\UploadedFile $file, string $defaultMessage): string
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
