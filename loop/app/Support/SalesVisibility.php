<?php

namespace App\Support;

use App\Models\PlatformSetting;

class SalesVisibility
{
    public const KEY = 'sales_visibility';

    /**
     * @return array{customers_see_sales: bool, front_desk_see_sales: bool}
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $stored = PlatformSetting::getValue(self::KEY, []);
        if (! is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    /**
     * @return array{customers_see_sales: bool, front_desk_see_sales: bool}
     */
    public static function defaults(): array
    {
        return [
            'customers_see_sales' => true,
            'front_desk_see_sales' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{customers_see_sales: bool, front_desk_see_sales: bool}
     */
    public static function normalizeInput(array $input): array
    {
        return [
            'customers_see_sales' => ! empty($input['customers_see_sales']),
            'front_desk_see_sales' => ! empty($input['front_desk_see_sales']),
        ];
    }

    public static function customersCanSee(): bool
    {
        return (bool) self::settings()['customers_see_sales'];
    }

    public static function frontDeskCanSee(): bool
    {
        return (bool) self::settings()['front_desk_see_sales'];
    }
}
