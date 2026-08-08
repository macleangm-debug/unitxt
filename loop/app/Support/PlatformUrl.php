<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\URL;

class PlatformUrl
{
    public const KEY = 'platform_url';

    /**
     * @return array{base_url: string}
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $stored = PlatformSetting::getValue(self::KEY, []);
        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'base_url' => self::normalizeBase((string) ($stored['base_url'] ?? $defaults['base_url'])),
        ];
    }

    /**
     * @return array{base_url: string}
     */
    public static function defaults(): array
    {
        return [
            'base_url' => rtrim((string) config('app.url'), '/'),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{base_url: string}
     */
    public static function normalizeInput(array $input): array
    {
        return [
            'base_url' => self::normalizeBase((string) ($input['base_url'] ?? '')),
        ];
    }

    public static function base(): string
    {
        $base = self::settings()['base_url'];

        return $base !== '' ? $base : rtrim((string) config('app.url'), '/');
    }

    public static function applyRootUrl(): void
    {
        $base = self::base();
        if ($base === '') {
            return;
        }

        URL::forceRootUrl($base);

        $scheme = parse_url($base, PHP_URL_SCHEME);
        if (is_string($scheme) && $scheme !== '') {
            URL::forceScheme($scheme);
        }
    }

    /**
     * Build an absolute URL using the configured public base.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        self::applyRootUrl();

        return route($name, $parameters, $absolute);
    }

    public static function whatsappShareUrl(string $message, ?string $e164Digits = null): string
    {
        $query = 'text='.rawurlencode($message);
        if ($e164Digits) {
            return 'https://wa.me/'.$e164Digits.'?'.$query;
        }

        return 'https://wa.me/?'.$query;
    }

    public static function smsShareUrl(string $message, ?string $phone = null): string
    {
        $body = rawurlencode($message);
        if ($phone) {
            return 'sms:'.$phone.'?&body='.$body;
        }

        return 'sms:?&body='.$body;
    }

    private static function normalizeBase(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return rtrim((string) config('app.url'), '/');
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return rtrim($url, '/');
    }
}
