<?php

namespace App\Support;

use App\Models\PlatformSetting;

class MarketingSettings
{
    public const KEY = 'marketing';

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
            'hero_tagline_override' => '',
            'pricing_blurb_override' => '',
            'show_affiliate_cta' => true,
            'show_referral_cta' => true,
            'launch_banner_enabled' => false,
            'launch_banner_text' => '',
            'holiday_message_enabled' => false,
            'holiday_message_text' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        return [
            'hero_tagline_override' => trim((string) ($input['hero_tagline_override'] ?? '')),
            'pricing_blurb_override' => trim((string) ($input['pricing_blurb_override'] ?? '')),
            'show_affiliate_cta' => ! empty($input['show_affiliate_cta']),
            'show_referral_cta' => ! empty($input['show_referral_cta']),
            'launch_banner_enabled' => ! empty($input['launch_banner_enabled']),
            'launch_banner_text' => trim((string) ($input['launch_banner_text'] ?? '')),
            'holiday_message_enabled' => ! empty($input['holiday_message_enabled']),
            'holiday_message_text' => trim((string) ($input['holiday_message_text'] ?? '')),
        ];
    }

    public static function pricingBlurb(): string
    {
        $override = trim((string) self::settings()['pricing_blurb_override']);
        if ($override !== '') {
            return $override;
        }

        return __('loop.pricing_blurb', [
            'days' => BillingSettings::settings()['trial_days'],
        ]);
    }

    public static function heroTagline(): string
    {
        $override = trim((string) self::settings()['hero_tagline_override']);

        return $override !== '' ? $override : __('loop.tagline');
    }
}
