<?php

namespace App\Support;

use App\Models\PlatformSetting;

class AffiliateProgram
{
    public const KEY = 'affiliate_program';

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

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'commission_percent' => 10,
            'referred_discount_percent' => 10,
            'attribution_enabled' => true,
            'attribution_months' => 12,
            'pin_length' => 4,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        return [
            'enabled' => ! empty($input['enabled']),
            'commission_percent' => max(1, min(50, (int) ($input['commission_percent'] ?? 10))),
            'referred_discount_percent' => max(0, min(50, (int) ($input['referred_discount_percent'] ?? 10))),
            'attribution_enabled' => ! empty($input['attribution_enabled']),
            'attribution_months' => max(1, min(36, (int) ($input['attribution_months'] ?? 12))),
            'pin_length' => max(4, min(6, (int) ($input['pin_length'] ?? 4))),
        ];
    }

    public static function isEnabled(): bool
    {
        return (bool) self::settings()['enabled'];
    }

    public static function commissionPercent(): int
    {
        return (int) self::settings()['commission_percent'];
    }

    public static function referredDiscountPercent(): int
    {
        return (int) self::settings()['referred_discount_percent'];
    }

    public static function pinLength(): int
    {
        return (int) self::settings()['pin_length'];
    }

    /**
     * Commission on the amount remaining after the business discount.
     */
    public static function commissionOn(int $planAmount, ?int $discountPercent = null, ?int $commissionPercent = null): array
    {
        $discountPercent = $discountPercent ?? self::referredDiscountPercent();
        $commissionPercent = $commissionPercent ?? self::commissionPercent();
        $discountAmount = (int) floor($planAmount * max(0, min(100, $discountPercent)) / 100);
        $netAmount = max(0, $planAmount - $discountAmount);
        $commissionAmount = (int) floor($netAmount * max(0, min(100, $commissionPercent)) / 100);

        return [
            'plan_amount' => $planAmount,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'net_amount' => $netAmount,
            'commission_percent' => $commissionPercent,
            'commission_amount' => $commissionAmount,
        ];
    }
}
