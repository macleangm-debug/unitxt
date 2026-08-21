<?php

namespace App\Support;

use Illuminate\Http\Request;

class GeoLocale
{
    /**
     * Countries that default to Swahili.
     *
     * @var list<string>
     */
    public const SWAHILI_COUNTRIES = ['TZ', 'KE'];

    public static function detectCountry(Request $request): ?string
    {
        $fromSession = $request->session()->get('preferred_country');
        if (is_string($fromSession) && Countries::isEnabled($fromSession)) {
            return $fromSession;
        }

        $header = $request->header('CF-IPCountry')
            ?? $request->header('X-AppEngine-Country')
            ?? $request->header('X-Country-Code');

        if (is_string($header)) {
            $code = strtoupper(trim($header));
            if (Countries::isEnabled($code)) {
                return $code;
            }
        }

        $ip = $request->ip();
        if ($ip && ! in_array($ip, ['127.0.0.1', '::1'], true)) {
            try {
                $json = @file_get_contents('http://ip-api.com/json/'.urlencode($ip).'?fields=status,countryCode', false, stream_context_create([
                    'http' => ['timeout' => 1.5],
                ]));
                if ($json) {
                    $data = json_decode($json, true);
                    $code = strtoupper((string) ($data['countryCode'] ?? ''));
                    if (($data['status'] ?? null) === 'success' && Countries::isEnabled($code)) {
                        return $code;
                    }
                }
            } catch (\Throwable) {
                // Ignore geo lookup failures.
            }
        }

        return Countries::snapToEnabled('TZ');
    }

    public static function defaultLocaleForCountry(?string $country): string
    {
        return in_array($country, self::SWAHILI_COUNTRIES, true) ? 'sw' : 'en';
    }
}
