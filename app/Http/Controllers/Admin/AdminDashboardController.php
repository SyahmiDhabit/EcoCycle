<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BusinessMailNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'businesses' => User::query()->latest()->get(),
            'pendingCount' => User::query()->where('account_status', 'pending')->count(),
            'approvedCount' => User::query()->where('account_status', 'approved')->count(),
            'rejectedCount' => User::query()->where('account_status', 'rejected')->count(),
        ]);
    }

    public function approve(User $business, BusinessMailNotifier $mailer): RedirectResponse
    {
        $business->update(['account_status' => 'approved']);
        $mailer->decision($business->fresh());

        return back()->with('status', "{$business->business_name} has been approved.");
    }

    public function reject(Request $request, User $business, BusinessMailNotifier $mailer): RedirectResponse
    {
        $validated = $request->validate(['review_note' => ['required', 'string', 'max:1000']]);
        $business->update(['account_status' => 'rejected', 'review_note' => $validated['review_note']]);
        $mailer->decision($business->fresh());

        return back()->with('status', "{$business->business_name} needs changes before approval.");
    }

    public function document(User $business, string $document): StreamedResponse
    {
        abort_unless(in_array($document, ['certificate', 'profile'], true), 404);

        $path = $document === 'certificate' ? $business->ssm_certificate_path : $business->ssm_profile_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
