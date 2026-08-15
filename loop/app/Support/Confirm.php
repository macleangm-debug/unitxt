<?php

namespace App\Support;

class Confirm
{
    /**
     * @return array{title: string, body: string, cta: string, url: string, celebrate: bool, tone: string, body_html?: string, dismiss?: bool}
     */
    public static function make(string $title, string $body, string $cta, string $url, bool $celebrate = true, array $extra = []): array
    {
        $tone = $extra['tone'] ?? ($celebrate ? 'success' : 'error');

        return array_merge([
            'title' => $title,
            'body' => $body,
            'cta' => $cta,
            'url' => $url,
            'celebrate' => $celebrate,
            'tone' => $tone,
        ], $extra);
    }

    public static function withBoldName(string $title, string $bodyKey, string $name, string $cta, string $url, bool $celebrate = true): array
    {
        $safe = e($name);

        return self::make(
            $title,
            __('loop.'.$bodyKey, ['name' => $name]),
            $cta,
            $url,
            $celebrate,
            [
                'body_html' => __('loop.'.$bodyKey, [
                    'name' => '<strong class="font-bold text-ink">'.$safe.'</strong>',
                ]),
            ]
        );
    }

    public static function error(string $title, string $body, string $cta, string $url): array
    {
        return self::make($title, $body, $cta, $url, false, [
            'tone' => 'error',
            'dismiss' => true,
        ]);
    }
}
