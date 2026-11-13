<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\BusinessNotification;
use App\Models\User;
use App\Services\BusinessMailNotifier;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'review_note' => null,
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

        $this->notifyDecision($business->fresh(), 'approved', null, $mailer);

        return back()->with('status', "{$business->business_name} has been approved.");
    }

    public function requestChanges(Request $request, User $business, BusinessMailNotifier $mailer): RedirectResponse
    {
        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        $business->update([
            'account_status' => 'rejected',
            'review_note' => $validated['review_note'],
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        $this->notifyDecision($business->fresh(), 'changes_requested', $validated['review_note'], $mailer);

        return back()->with('status', "{$business->business_name} needs changes before approval.");
    }

    public function reject(Request $request, User $business, BusinessMailNotifier $mailer): RedirectResponse
    {
        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
            'action_type' => ['nullable', 'string', 'in:request_changes,reject'],
        ]);

        $actionType = $validated['action_type'] ?? $request->input('action_type', 'request_changes');
        if (! in_array($actionType, ['request_changes', 'reject'], true)) {
            $actionType = 'request_changes';
        }
        $decisionType = $actionType === 'reject' ? 'rejected' : 'changes_requested';

        $business->update([
            'account_status' => 'rejected',
            'review_note' => $validated['review_note'],
            'reviewed_by' => Auth::guard('admin')->id(),
            'reviewed_at' => now(),
        ]);

        $this->notifyDecision($business->fresh(), $decisionType, $validated['review_note'], $mailer);

        $statusMsg = $decisionType === 'changes_requested'
            ? "{$business->business_name} needs changes before approval."
            : "{$business->business_name} registration has been rejected.";

        return back()->with('status', $statusMsg);
    }

    private function notifyDecision(User $business, string $decisionType, ?string $reviewNote, BusinessMailNotifier $mailer): void
    {
        $cacheKey = "last_business_decision_{$business->id}";
        $lastDecision = Cache::get($cacheKey);

        $isDuplicate = is_array($lastDecision)
            && ($lastDecision['decision'] ?? null) === $decisionType
            && ($lastDecision['note'] ?? null) === $reviewNote
            && (now()->timestamp - ($lastDecision['time'] ?? 0)) < 1;

        if ($isDuplicate) {
            return;
        }

        Cache::put($cacheKey, [
            'decision' => $decisionType,
            'note' => $reviewNote,
            'time' => now()->timestamp,
        ], 3);

        $mailer->decision($business, $decisionType);
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

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->download($path);
    }
}
