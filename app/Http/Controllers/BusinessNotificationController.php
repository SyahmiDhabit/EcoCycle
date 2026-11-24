<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessNotificationController extends Controller
{

    public function markAsRead(Request $request, int $notification): JsonResponse
    {
        /** @var User $business */
        $business = $request->user();
        $entry = $business->notifications()->findOrFail($notification);

        $business->notifications()->whereKey($entry->getKey())->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $business */
        $business = Auth::user();

        if ($business) {
            $business->notifications()->where('is_read', false)->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
