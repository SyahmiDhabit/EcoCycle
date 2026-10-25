<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BusinessDashboardController extends Controller
{
    public function __invoke(): View
    {
        $business = Auth::user();

        if ($business->account_status !== 'approved') {
            return view('business.verification', ['business' => $business]);
        }

        return view('business.dashboard', ['business' => $business]);
    }
}
