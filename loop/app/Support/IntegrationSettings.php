<?php

namespace App\Support;

use App\Models\PlatformSetting;

class IntegrationSettings
{
    public const KEY = 'integrations';

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $stored = PlatformSetting::getValue(self::KEY, []);
        if (! is_array($stored)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, array_intersect_key($stored, $defaults));
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'payments' => [
                'primary' => 'payin',
                'secondary' => null,
                'providers' => [
                    'payin' => [
                        'enabled' => true,
                        'mode' => 'sandbox',
                        'api_key' => '',
                        'api_secret' => '',
                        'webhook_secret' => '',
                        'docs_url' => 'https://docs.payin.co.tz/',
                    ],
                ],
            ],
            'messaging' => [
                'enabled' => false,
                'provider' => 'stub',
                'sender_id' => 'Loop',
                'api_key' => '',
                'api_secret' => '',
                'api_url' => '',
                'business_can_message_customers' => true,
                'platform_can_message_businesses' => true,
                'price_per_message' => 30,
                'chars_per_message' => 160,
                'sender_id_yearly_fee' => 15000,
                'enabled_countries' => ['TZ'],
            ],
            'email' => [
                'enabled' => false,
                'provider' => 'smtp',
                'from_name' => 'Loop',
                'from_address' => '',
                'api_key' => '',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        $current = self::settings();

        if (array_key_exists('primary', $input) || isset($input['payin'])) {
            if (isset($input['primary']) && in_array($input['primary'], ['payin'], true)) {
                $current['payments']['primary'] = $input['primary'];
            }
            if (array_key_exists('secondary', $input)) {
                $current['payments']['secondary'] = filled($input['secondary'] ?? null) ? (string) $input['secondary'] : null;
            }
            if (isset($input['payin']) && is_array($input['payin'])) {
                $payin = $input['payin'];
                $prev = $current['payments']['providers']['payin'];
                $current['payments']['providers']['payin'] = [
                    'enabled' => ! empty($payin['enabled']),
                    'mode' => in_array(($payin['mode'] ?? 'sandbox'), ['sandbox', 'live'], true) ? $payin['mode'] : 'sandbox',
                    'api_key' => array_key_exists('api_key', $payin) ? (string) $payin['api_key'] : $prev['api_key'],
                    'api_secret' => array_key_exists('api_secret', $payin) ? (string) $payin['api_secret'] : $prev['api_secret'],
                    'webhook_secret' => array_key_exists('webhook_secret', $payin) ? (string) $payin['webhook_secret'] : $prev['webhook_secret'],
                    'docs_url' => 'https://docs.payin.co.tz/',
                ];
            }
        }

        if (isset($input['messaging']) && is_array($input['messaging'])) {
            $m = $input['messaging'];
            $current['messaging'] = [
                'enabled' => ! empty($m['enabled']),
                'provider' => (string) ($m['provider'] ?? 'stub'),
                'sender_id' => (string) ($m['sender_id'] ?? 'Loop'),
                'api_key' => (string) ($m['api_key'] ?? ''),
                'api_secret' => (string) ($m['api_secret'] ?? ''),
                'api_url' => (string) ($m['api_url'] ?? ''),
                'business_can_message_customers' => ! empty($m['business_can_message_customers']),
                'platform_can_message_businesses' => ! empty($m['platform_can_message_businesses']),
                'price_per_message' => max(1, (int) ($m['price_per_message'] ?? 30)),
                'chars_per_message' => max(1, min(320, (int) ($m['chars_per_message'] ?? 160))),
                'sender_id_yearly_fee' => max(0, (int) ($m['sender_id_yearly_fee'] ?? 15000)),
                'enabled_countries' => self::normalizeCountries($m['enabled_countries'] ?? ['TZ']),
            ];
        }

        if (isset($input['email']) && is_array($input['email'])) {
            $e = $input['email'];
            $current['email'] = [
                'enabled' => ! empty($e['enabled']),
                'provider' => (string) ($e['provider'] ?? 'smtp'),
                'from_name' => (string) ($e['from_name'] ?? 'Loop'),
                'from_address' => (string) ($e['from_address'] ?? ''),
                'api_key' => (string) ($e['api_key'] ?? ''),
            ];
        }

        return $current;
    }

    /**
     * @param  mixed  $input
     * @return list<string>
     */
    private static function normalizeCountries(mixed $input): array
    {
        $raw = is_array($input) ? $input : (preg_split('/[\s,]+/', (string) $input) ?: []);
        $out = [];
        foreach ($raw as $code) {
            $code = strtoupper(trim((string) $code));
            if (strlen($code) === 2) {
                $out[] = $code;
            }
        }

        return $out !== [] ? array_values(array_unique($out)) : ['TZ'];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function mergeMessagingRates(array $input): array
    {
        $current = self::settings();
        $current['messaging']['price_per_message'] = max(1, (int) ($input['price_per_message'] ?? $current['messaging']['price_per_message'] ?? 30));
        $current['messaging']['chars_per_message'] = max(1, min(320, (int) ($input['chars_per_message'] ?? $current['messaging']['chars_per_message'] ?? 160)));
        $current['messaging']['sender_id_yearly_fee'] = max(0, (int) ($input['sender_id_yearly_fee'] ?? $current['messaging']['sender_id_yearly_fee'] ?? 15000));

        return $current;
    }

    public static function primaryProvider(): string
    {
        return (string) (self::settings()['payments']['primary'] ?? 'payin');
    }

    /**
     * @return array<string, mixed>
     */
    public static function provider(string $key): array
    {
        return self::settings()['payments']['providers'][$key] ?? [];
    }
}
