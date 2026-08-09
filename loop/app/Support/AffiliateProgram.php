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
            'cookie_days' => 30,
            'pin_length' => 4,
            // KPIs — affiliates are governed by these targets
            'kpi_enabled' => true,
            'monthly_paying_business_target' => 5,
            'show_kpis_to_affiliates' => true,
            // International affiliate program standards
            'block_self_referral' => true,
            'min_payout_amount' => 50000,
            'payout_schedule' => 'monthly', // weekly|biweekly|monthly
            'tax_withholding_percent' => 0,
            'require_tax_id' => false,
            'terms_url' => '',
            'fraud_hold_days' => 14,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        $schedule = (string) ($input['payout_schedule'] ?? 'monthly');
        if (! in_array($schedule, ['weekly', 'biweekly', 'monthly'], true)) {
            $schedule = 'monthly';
        }

        return [
            'enabled' => ! empty($input['enabled']),
            'commission_percent' => max(1, min(50, (int) ($input['commission_percent'] ?? 10))),
            'referred_discount_percent' => max(0, min(50, (int) ($input['referred_discount_percent'] ?? 10))),
            'attribution_enabled' => ! empty($input['attribution_enabled']),
            'attribution_months' => max(1, min(36, (int) ($input['attribution_months'] ?? 12))),
            'cookie_days' => max(1, min(365, (int) ($input['cookie_days'] ?? 30))),
            'pin_length' => max(4, min(6, (int) ($input['pin_length'] ?? 4))),
            'kpi_enabled' => ! empty($input['kpi_enabled']),
            'monthly_paying_business_target' => max(1, min(100, (int) ($input['monthly_paying_business_target'] ?? 5))),
            'show_kpis_to_affiliates' => ! empty($input['show_kpis_to_affiliates']),
            'block_self_referral' => ! empty($input['block_self_referral']),
            'min_payout_amount' => max(0, (int) ($input['min_payout_amount'] ?? 50000)),
            'payout_schedule' => $schedule,
            'tax_withholding_percent' => max(0, min(40, (int) ($input['tax_withholding_percent'] ?? 0))),
            'require_tax_id' => ! empty($input['require_tax_id']),
            'terms_url' => trim((string) ($input['terms_url'] ?? '')),
            'fraud_hold_days' => max(0, min(90, (int) ($input['fraud_hold_days'] ?? 14))),
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

    public static function monthlyPayingTarget(): int
    {
        return (int) self::settings()['monthly_paying_business_target'];
    }

    /**
     * Commission on the amount remaining after the business discount.
     *
     * @return array{plan_amount: int, discount_percent: int, discount_amount: int, net_amount: int, commission_percent: int, commission_amount: int}
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
