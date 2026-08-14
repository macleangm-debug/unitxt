<?php

namespace App\Http\Controllers;

use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;

class PreferenceController extends Controller
{
    /** Paths that only accept POST — never use as locale return targets. */
    private const UNSAFE_RETURN_NAMES = [
        'till.lookup',
        'till.store',
        'till.register-customer',
    ];

    public function locale(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['en', 'sw'], true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);

        if ($request->user()) {
            $request->user()->forceFill(['locale' => $locale])->save();
        }

        $target = $this->safeReturnUrl($request);

        return redirect()->to($target);
    }

    public function country(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'country' => ['required', 'in:'.implode(',', array_keys(Countries::OPTIONS))],
        ]);

        $request->session()->put('preferred_country', $data['country']);

        return back();
    }

    private function safeReturnUrl(Request $request): string
    {
        $return = $request->query('return');
        $candidate = null;

        if (is_string($return) && $return !== '') {
            if (str_starts_with($return, '/') && ! str_starts_with($return, '//')) {
                $candidate = $return;
            } else {
                $returnHost = parse_url($return, PHP_URL_HOST);
                if ($returnHost && $returnHost === $request->getHost()) {
                    $candidate = $return;
                }
            }
        }

        if (! $candidate) {
            $previous = url()->previous();
            if ($previous) {
                if (str_starts_with($previous, '/') && ! str_starts_with($previous, '//')) {
                    $candidate = $previous;
                } else {
                    $prevHost = parse_url($previous, PHP_URL_HOST);
                    if ($prevHost && $prevHost === $request->getHost()) {
                        $candidate = $previous;
                    }
                }
            }
        }

        if ($candidate && $this->isSafeGetUrl($candidate)) {
            return $candidate;
        }

        if ($request->user()) {
            return route('dashboard');
        }

        return route('home');
    }

    private function isSafeGetUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        try {
            $route = RouteFacade::getRoutes()->match(
                Request::create($path, 'GET')
            );
        } catch (\Throwable) {
            // Path may be POST-only — try to map known till paths.
            if (str_starts_with($path, '/sale')) {
                return false;
            }

            return true;
        }

        $name = $route->getName();
        if ($name && in_array($name, self::UNSAFE_RETURN_NAMES, true)) {
            return false;
        }

        $methods = $route->methods();
        if (! in_array('GET', $methods, true) && ! in_array('HEAD', $methods, true)) {
            return false;
        }

        return true;
    }
}
