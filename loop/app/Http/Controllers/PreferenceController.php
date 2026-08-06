<?php

namespace App\Http\Controllers;

use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function locale(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['en', 'sw'], true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        return back();
    }

    public function country(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country' => ['required', 'in:'.implode(',', array_keys(Countries::OPTIONS))],
        ]);

        $request->session()->put('preferred_country', $data['country']);

        return back();
    }
}
