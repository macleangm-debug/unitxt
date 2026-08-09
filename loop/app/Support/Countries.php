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
            'Dar es Salaam' => ['Masaki', 'Mikocheni', 'Kinondoni', 'Kawe', 'Mbezi', 'Upanga', 'Oysterbay', 'Sinza', 'Mbezi Beach', 'Kariakoo'],
            'Arusha' => ['Njiro', 'Sakina', 'Kaloleni', 'Sombetini', 'Themi'],
            'Mwanza' => ['Isamilo', 'Nyamagana', 'Ilemela', 'Pamba'],
            'Nairobi' => ['Westlands', 'Kilimani', 'Karen', 'Lavington', 'CBD', 'Eastleigh'],
            'Mombasa' => ['Nyali', 'Bamburi', 'Old Town', 'Likoni'],
            'Kampala' => ['Kololo', 'Nakasero', 'Bugolobi', 'Ntinda', 'Makerere'],
            'Kigali' => ['Kimihurura', 'Nyarutarama', 'Remera', 'Kacyiru'],
            'Harare' => ['Borrowdale', 'Avondale', 'CBD', 'Mount Pleasant'],
        ];

        return $map[$city] ?? [];
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
