<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\BusinessMailNotifier;

class BusinessApplicationController extends Controller
{
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

        $mailer->submitted($business->fresh());

        return redirect()->route('business.dashboard')->with('status', 'Your updated SSM profile has been resubmitted for administrator review.');
    }
}
