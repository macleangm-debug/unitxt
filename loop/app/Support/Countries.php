<?php

namespace App\Support;

class Countries
{
    public const OPTIONS = [
        'TZ' => [
            'name' => 'Tanzania',
            'dial' => '+255',
            'currency' => 'TZS',
            'flag' => '🇹🇿',
            'cities' => [
                'Dar es Salaam', 'Arusha', 'Mwanza', 'Dodoma', 'Mbeya', 'Morogoro',
                'Tanga', 'Kahama', 'Tabora', 'Zanzibar City', 'Kigoma', 'Sumbawanga',
                'Kasulu', 'Songea', 'Moshi', 'Musoma', 'Shinyanga', 'Iringa', 'Singida',
                'Njombe', 'Bukoba', 'Lindi', 'Mtwara', 'Babati', 'Geita',
            ],
        ],
        'KE' => [
            'name' => 'Kenya',
            'dial' => '+254',
            'currency' => 'KES',
            'flag' => '🇰🇪',
            'cities' => ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi', 'Kitale', 'Garissa', 'Nyeri'],
        ],
        'UG' => [
            'name' => 'Uganda',
            'dial' => '+256',
            'currency' => 'UGX',
            'flag' => '🇺🇬',
            'cities' => ['Kampala', 'Entebbe', 'Jinja', 'Gulu', 'Mbarara', 'Mbale', 'Fort Portal'],
        ],
        'RW' => [
            'name' => 'Rwanda',
            'dial' => '+250',
            'currency' => 'RWF',
            'flag' => '🇷🇼',
            'cities' => ['Kigali', 'Butare', 'Gisenyi', 'Ruhengeri', 'Musanze'],
        ],
        'BI' => [
            'name' => 'Burundi',
            'dial' => '+257',
            'currency' => 'BIF',
            'flag' => '🇧🇮',
            'cities' => ['Bujumbura', 'Gitega', 'Ngozi', 'Rumonge'],
        ],
        'CD' => [
            'name' => 'DR Congo',
            'dial' => '+243',
            'currency' => 'CDF',
            'flag' => '🇨🇩',
            'cities' => ['Kinshasa', 'Lubumbashi', 'Goma', 'Bukavu', 'Kisangani', 'Mbuji-Mayi'],
        ],
        'ZW' => [
            'name' => 'Zimbabwe',
            'dial' => '+263',
            'currency' => 'USD',
            'flag' => '🇿🇼',
            'cities' => ['Harare', 'Bulawayo', 'Mutare', 'Gweru', 'Kwekwe', 'Masvingo'],
        ],
    ];

    public static function dial(string $country = 'TZ'): string
    {
        return self::OPTIONS[$country]['dial'] ?? '+255';
    }

    public static function currency(string $country = 'TZ'): string
    {
        return self::OPTIONS[$country]['currency'] ?? 'TZS';
    }

    /**
     * Markets enabled in Settings Hub (expansion-ready).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function enabledOptions(): array
    {
        $enabled = CountrySettings::settings()['enabled'];
        $out = [];
        foreach ($enabled as $code) {
            if (isset(self::OPTIONS[$code])) {
                $out[$code] = self::OPTIONS[$code];
            }
        }

        return $out !== [] ? $out : ['TZ' => self::OPTIONS['TZ']];
    }

    /**
     * ISO codes currently on for new acquisition (register, apply, Discover, header).
     *
     * @return list<string>
     */
    public static function enabledCodes(): array
    {
        return array_keys(self::enabledOptions());
    }

    public static function isEnabled(string $code): bool
    {
        return isset(self::enabledOptions()[strtoupper($code)]);
    }

    /**
     * Validation rule for choosing a market that is on in Settings Hub.
     */
    public static function enabledRule(): string
    {
        return 'in:'.implode(',', self::enabledCodes());
    }

    /**
     * Login / activate pickers: enabled markets plus any market that already
     * has a Loop user or affiliate, so turning a country off does not lock
     * existing people out.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function authOptions(): array
    {
        $out = self::enabledOptions();
        try {
            $used = [];
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                $used = array_merge($used, \Illuminate\Support\Facades\DB::table('users')
                    ->whereNotNull('country')
                    ->distinct()
                    ->pluck('country')
                    ->all());
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('affiliates')) {
                $used = array_merge($used, \Illuminate\Support\Facades\DB::table('affiliates')
                    ->whereNotNull('country')
                    ->distinct()
                    ->pluck('country')
                    ->all());
            }
            foreach ($used as $code) {
                $code = strtoupper((string) $code);
                if (isset(self::OPTIONS[$code]) && ! isset($out[$code])) {
                    $out[$code] = self::OPTIONS[$code];
                }
            }
        } catch (\Throwable) {
            // Schema may be missing during early migrate.
        }

        return $out;
    }

    public static function authRule(): string
    {
        return 'in:'.implode(',', array_keys(self::authOptions()));
    }

    /**
     * Selectors that may keep a currently stored country after Admin turns that market off.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function formOptions(?string $keep = null): array
    {
        $out = self::enabledOptions();
        $keep = strtoupper((string) $keep);
        if ($keep !== '' && isset(self::OPTIONS[$keep]) && ! isset($out[$keep])) {
            $out[$keep] = self::OPTIONS[$keep];
        }

        return $out;
    }

    public static function formRule(?string $keep = null): string
    {
        return 'in:'.implode(',', array_keys(self::formOptions($keep)));
    }

    /**
     * Snap a stored/detected code onto an enabled market.
     */
    public static function snapToEnabled(?string $code, string $fallback = 'TZ'): string
    {
        $code = strtoupper((string) $code);
        if (self::isEnabled($code)) {
            return $code;
        }
        $enabled = self::enabledCodes();
        if (in_array($fallback, $enabled, true)) {
            return $fallback;
        }

        return $enabled[0] ?? 'TZ';
    }

    public static function cities(string $country = 'TZ'): array
    {
        return self::OPTIONS[$country]['cities'] ?? [];
    }

    /**
     * Popular area / street suggestions after a city is chosen.
     *
     * @return list<string>
     */
    public static function areas(string $city): array
    {
        $map = [
            'Dar es Salaam' => ['Ilala', 'Kinondoni', 'Temeke', 'Ubungo', 'Kigamboni', 'Masaki', 'Mikocheni', 'Kawe', 'Mbezi', 'Upanga', 'Oysterbay', 'Kariakoo'],
            'Arusha' => ['Arusha', 'Meru', 'Karatu', 'Monduli', 'Ngorongoro', 'Longido', 'Njiro', 'Sakina'],
            'Mwanza' => ['Nyamagana', 'Ilemela', 'Isamilo', 'Pamba'],
            'Dodoma' => ['Dodoma', 'Bahi', 'Chamwino', 'Chemba', 'Kondoa', 'Mpwapwa'],
            'Mbeya' => ['Mbeya', 'Kyela', 'Rungwe', 'Chunya', 'Mbarali'],
            'Morogoro' => ['Morogoro', 'Kilombero', 'Kilosa', 'Mvomero', 'Ulanga'],
            'Tanga' => ['Tanga', 'Korogwe', 'Muheza', 'Pangani', 'Lushoto'],
            'Moshi' => ['Moshi', 'Hai', 'Mwanga', 'Rombo', 'Same'],
            'Zanzibar City' => ['Mjini', 'Magharibi A', 'Magharibi B', 'Kati', 'Kaskazini A'],
            'Nairobi' => ['Westlands', 'Kilimani', 'Karen', 'Lavington', 'CBD', 'Eastleigh', 'Kasarani', 'Langata'],
            'Mombasa' => ['Nyali', 'Bamburi', 'Old Town', 'Likoni', 'Kisauni', 'Changamwe'],
            'Kisumu' => ['Kisumu Central', 'Kisumu East', 'Kisumu West'],
            'Kampala' => ['Kampala Central', 'Kawempe', 'Makindye', 'Nakawa', 'Rubaga', 'Kololo', 'Ntinda'],
            'Entebbe' => ['Entebbe', 'Katabi', 'Kigungu'],
            'Kigali' => ['Gasabo', 'Kicukiro', 'Nyarugenge', 'Kimihurura', 'Remera', 'Kacyiru'],
            'Bujumbura' => ['Mukaza', 'Muha', 'Ntahangwa'],
            'Kinshasa' => ['Gombe', 'Limete', 'Ngaliema', 'Kalamu', 'Lemba'],
            'Harare' => ['Harare Urban', 'Borrowdale', 'Avondale', 'CBD', 'Mount Pleasant'],
            'Bulawayo' => ['Bulawayo', 'Cowdray Park', 'Nkulumane'],
        ];

        return $map[$city] ?? [];
    }

    /**
     * District / area options for a city dropdown. Falls back to the city itself.
     *
     * @return list<string>
     */
    public static function districts(string $city): array
    {
        $areas = self::areas($city);

        return $areas !== [] ? $areas : ($city !== '' ? [$city] : []);
    }

    public static function fromDial(string $dial): ?string
    {
        foreach (self::OPTIONS as $code => $meta) {
            if ($meta['dial'] === $dial) {
                return $code;
            }
        }

        return null;
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}
