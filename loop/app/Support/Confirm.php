<?php

namespace App\Support;

class Confirm
{
    /**
     * @return array{title: string, body: string, cta: string, url: string, celebrate: bool}
     */
    public static function make(string $title, string $body, string $cta, string $url, bool $celebrate = true): array
    {
        return [
            'title' => $title,
            'body' => $body,
            'cta' => $cta,
            'url' => $url,
            'celebrate' => $celebrate,
        ];
    }
}
