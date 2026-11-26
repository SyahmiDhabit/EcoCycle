<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['locale' => ['required', 'in:en,ms']]);
        $request->session()->put('locale', $validated['locale']);

        $previous = $request->session()->previousUrl();
        $returnUrl = is_string($previous) && str_starts_with($previous, $request->getSchemeAndHttpHost().'/')
            ? $previous
            : route('home');

        return redirect()->to($returnUrl)
            ->withCookie(cookie('ecocycle_locale', $validated['locale'], 525600));
    }
}
