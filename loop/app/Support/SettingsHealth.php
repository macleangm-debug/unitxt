<?php

namespace App\Support;

use App\Models\ExceptionHit;
use App\Models\PlatformSetting;

class SettingsHealth
{
    /**
     * @return list<array{key: string, label: string, status: string, detail: string}>
     */
    public static function checks(): array
    {
        $countries = Countries::enabledCodes();
        $affiliate = AffiliateProgram::settings();
        $flags = FeatureFlags::settings();
        $integrations = IntegrationSettings::settings();
        $payinOn = ! empty($integrations['payments']['providers']['payin']['enabled'] ?? false);
        $smsOn = ! empty($integrations['messaging']['enabled'] ?? false);
        $openErrors = ExceptionHit::openCount();

        return [
            [
                'key' => 'packages',
                'label' => __('loop.settings_tab_packages'),
                'status' => 'ok',
                'detail' => __('loop.health_packages_ok'),
            ],
            [
                'key' => 'billing',
                'label' => __('loop.settings_tab_billing'),
                'status' => 'ok',
                'detail' => __('loop.health_billing_ok', [
                    'days' => BillingSettings::settings()['trial_days'],
                    'grace' => BillingSettings::settings()['grace_days'],
                ]),
            ],
            [
                'key' => 'countries',
                'label' => __('loop.settings_tab_countries'),
                'status' => 'ok',
                'detail' => __('loop.health_countries_ok', ['list' => implode(', ', $countries)]),
            ],
            [
                'key' => 'affiliates',
                'label' => __('loop.settings_tab_affiliates'),
                'status' => 'ok',
                'detail' => $affiliate['enabled']
                    ? __('loop.health_affiliates_on', ['pct' => $affiliate['commission_percent']])
                    : __('loop.health_affiliates_off'),
            ],
            [
                'key' => 'referrals',
                'label' => __('loop.settings_tab_referrals'),
                'status' => 'ok',
                'detail' => __('loop.health_referrals_ok'),
            ],
            [
                'key' => 'raffles',
                'label' => __('loop.raffles'),
                'status' => $flags['raffles'] ? 'ok' : 'off',
                'detail' => $flags['raffles']
                    ? __('loop.health_raffles_on', ['min' => GrowthSettings::raffleMinMembers()])
                    : __('loop.health_flag_off'),
            ],
            [
                'key' => 'games',
                'label' => __('loop.games_wins'),
                'status' => ($flags['games'] ?? false) && ! empty(GameSettings::settings()['enabled']) ? 'ok' : 'off',
                'detail' => ($flags['games'] ?? false) && ! empty(GameSettings::settings()['enabled'])
                    ? __('loop.health_games_on', ['rate' => GameSettings::settings()['recommended_win_rate']])
                    : __('loop.health_flag_off'),
            ],
            [
                'key' => 'content_studio',
                'label' => __('loop.content_studio'),
                'status' => $flags['content_studio'] ? 'ok' : 'off',
                'detail' => $flags['content_studio'] ? __('loop.health_studio_on') : __('loop.health_flag_off'),
            ],
            [
                'key' => 'notifications',
                'label' => __('loop.settings_tab_notifications'),
                'status' => 'ok',
                'detail' => __('loop.health_notifications_ok'),
            ],
            [
                'key' => 'integrations',
                'label' => __('loop.integrations_hub'),
                'status' => ($payinOn || $smsOn) ? 'ok' : 'warn',
                'detail' => __('loop.health_integrations_detail', [
                    'payin' => $payinOn ? __('loop.on') : __('loop.off'),
                    'sms' => $smsOn ? __('loop.on') : __('loop.off'),
                ]),
            ],
            [
                'key' => 'errors',
                'label' => __('loop.admin_errors'),
                'status' => $openErrors > 0 ? 'warn' : 'ok',
                'detail' => $openErrors > 0
                    ? trans_choice('loop.health_errors_open', $openErrors, ['count' => $openErrors])
                    : __('loop.health_errors_ok'),
            ],
            [
                'key' => 'hub_row',
                'label' => __('loop.health_platform_row'),
                'status' => PlatformSetting::query()->exists() ? 'ok' : 'warn',
                'detail' => __('loop.health_platform_row_detail'),
            ],
        ];
    }
}
