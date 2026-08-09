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

        $return = $request->query('return');
        if (is_string($return) && $return !== '') {
            if (str_starts_with($return, '/') && ! str_starts_with($return, '//')) {
                return redirect()->to($return);
            }

            $returnHost = parse_url($return, PHP_URL_HOST);
            if ($returnHost && $returnHost === $request->getHost()) {
                return redirect()->to($return);
            }
        }

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
