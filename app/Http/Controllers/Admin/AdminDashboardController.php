<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\BusinessNotification;
use App\Models\User;
use App\Services\BusinessMailNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'businesses' => User::query()
                ->withCount(['inventoryItems', 'marketplaceListings'])
                ->latest()
                ->get(),
            'pendingCount' => User::query()->where('account_status', 'pending')->count(),
            'approvedCount' => User::query()->where('account_status', 'approved')->count(),
            'rejectedCount' => User::query()->where('account_status', 'rejected')->count(),
            'notifications' => AdminNotification::query()->latest()->take(10)->get(),
            'unreadNotificationsCount' => AdminNotification::query()->where('is_read', false)->count(),
        ]);
    }

    public function approve(User $business, BusinessMailNotifier $mailer): RedirectResponse
    {
        $business->update([
            'account_status' => 'approved',
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        BusinessNotification::send(
            $business->id,
            'Business Verification Approved',
            'Your business application has been approved by the platform administrator. You now have full verified access to the circular marketplace, inventory stock, and material exchange directory.',
            'verification_approved',
            '/business',
            'green'
        );

        $mailer->decision($business->fresh());

        return back()->with('status', "{$business->business_name} has been approved.");
    }

    public function reject(Request $request, User $business, BusinessMailNotifier $mailer): RedirectResponse
    {
        $validated = $request->validate(['review_note' => ['required', 'string', 'max:1000']]);
        $business->update([
            'account_status' => 'rejected',
            'review_note' => $validated['review_note'],
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        // Rejections are communicated exclusively via email as requested
        $mailer->decision($business->fresh());

        return back()->with('status', "{$business->business_name} needs changes before approval.");
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        AdminNotification::query()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    public function document(User $business, string $document): StreamedResponse
    {
        abort_unless(in_array($document, ['certificate', 'profile'], true), 404);

        $path = $document === 'certificate' ? $business->ssm_certificate_path : $business->ssm_profile_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->download($path);
    }
}
