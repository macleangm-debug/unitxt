<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['en', 'sw'], true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return back();
    }
}
