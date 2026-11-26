<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', $request->cookie('ecocycle_locale', 'en'));
        App::setLocale(in_array($locale, ['en', 'ms'], true) ? $locale : 'en');

        return $next($request);
    }
}
