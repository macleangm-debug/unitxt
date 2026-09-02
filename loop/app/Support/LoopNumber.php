<?php

namespace App\Support;

class LoopNumber
{
    /**
     * Grouping for every user-facing amount. Stored values stay raw.
     */
    public static function format(int|float|string|null $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return $decimals > 0 ? number_format(0, $decimals, '.', ',') : '0';
        }

        return number_format((float) $value, $decimals, '.', ',');
    }

    public static function money(int|float|string|null $amount, string $currency, ?int $decimals = null): string
    {
        $decimals ??= self::decimals($currency);

        return trim($currency.' '.self::format($amount, $decimals));
    }

    public static function parse(int|float|string|null $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = preg_replace('/[^\d.-]/', '', str_replace(',', '', (string) $value));

        return is_numeric($raw) ? (float) $raw : 0.0;
    }

    public static function decimals(string $currency): int
    {
        return in_array(strtoupper($currency), ['USD', 'KES', 'EUR', 'GBP'], true) ? 2 : 0;
    }
}
