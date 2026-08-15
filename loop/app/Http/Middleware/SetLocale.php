<?php

namespace App\Http\Middleware;

use App\Support\GeoLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        if (! $session->has('preferred_country')) {
            $country = GeoLocale::detectCountry($request);
            if ($country) {
                $session->put('preferred_country', $country);
            }
        }

        if (! $session->has('locale')) {
            $userLocale = $request->user()?->locale;
            if (in_array($userLocale, ['en', 'sw'], true)) {
                $session->put('locale', $userLocale);
            } else {
                $country = $session->get('preferred_country', 'TZ');
                $session->put('locale', GeoLocale::defaultLocaleForCountry($country));
            }
        }

        $locale = $session->get('locale', config('app.locale', 'en'));

        if (in_array($locale, ['en', 'sw'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
