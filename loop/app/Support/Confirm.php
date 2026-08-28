<?php

namespace App\Support;

class Confirm
{
    /**
     * @return array{title: string, body: string, cta: string, url: string, celebrate: bool, body_html?: string}
     */
    public static function make(string $title, string $body, string $cta, string $url, bool $celebrate = true, array $extra = []): array
    {
        return array_merge([
            'title' => $title,
            'body' => $body,
            'cta' => $cta,
            'url' => $url,
            'celebrate' => $celebrate,
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

    /**
     * Page feedback: session confirm wins, then a status flash, then the first error.
     *
     * @param  array<string, mixed>|null  $confirm
     */
    public static function resolve(?array $confirm, mixed $status = null, mixed $errors = null): ?array
    {
        if ($confirm) {
            return $confirm;
        }

        $skip = ['profile-updated', 'password-updated', 'verification-link-sent'];
        if (is_string($status) && $status !== '' && ! in_array($status, $skip, true)) {
            return self::make($status, '', __('loop.done'), url()->current(), false, ['dismiss' => true]);
        }

        if ($errors && method_exists($errors, 'any') && $errors->any()) {
            return self::make(
                __('loop.feedback_title'),
                (string) $errors->first(),
                __('loop.try_again'),
                url()->current(),
                false,
                ['kind' => 'error', 'dismiss' => true]
            );
        }

        return null;
    }
}
