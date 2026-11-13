<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessIsApproved
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = Auth::guard('web')->user() ?: $request->user();

        if (! $business) {
            if (Auth::guard('admin')->check()) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('login');
        }

        if ($business->account_status !== 'approved') {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your business account is waiting for administrator approval.',
                ], 403);
            }

            return redirect()->route('business.pending');
        }

        return $next($request);
    }
}
