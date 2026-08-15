<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Reward;

class ContentStudio
{
    /**
     * @return list<array{key: string, name: string, w: int, h: int, label: string}>
     */
    public static function sizes(): array
    {
        return [
            ['key' => 'ig_post', 'name' => __('loop.studio_size_ig_post'), 'w' => 1080, 'h' => 1080, 'label' => '1:1'],
            ['key' => 'ig_portrait', 'name' => __('loop.studio_size_ig_portrait'), 'w' => 1080, 'h' => 1350, 'label' => '4:5'],
            ['key' => 'ig_story', 'name' => __('loop.studio_size_ig_story'), 'w' => 1080, 'h' => 1920, 'label' => '9:16'],
            ['key' => 'ig_landscape', 'name' => __('loop.studio_size_ig_landscape'), 'w' => 1080, 'h' => 566, 'label' => '1.91:1'],
        ];
    }

    /**
     * @return list<array{key: string, name: string}>
     */
    public static function modes(): array
    {
        return [
            ['key' => 'clean_type', 'name' => __('loop.studio_mode_clean')],
            ['key' => 'photo_story', 'name' => __('loop.studio_mode_photo')],
        ];
    }

    /**
     * @return list<array{key: string, name: string, accent: string, text: string}>
     */
    public static function designs(): array
    {
        return [
            ['key' => 'mint_card', 'name' => __('loop.studio_design_mint'), 'accent' => '#B8F26E', 'text' => '#111114'],
            ['key' => 'ink_bold', 'name' => __('loop.studio_design_ink'), 'accent' => '#111114', 'text' => '#FFFFFF'],
            ['key' => 'coral_pop', 'name' => __('loop.studio_design_coral'), 'accent' => '#FF6B4A', 'text' => '#111114'],
            ['key' => 'cream_soft', 'name' => __('loop.studio_design_cream'), 'accent' => '#F7F3EA', 'text' => '#111114'],
        ];
    }

    /**
     * @return list<array{key: string, hex: string, name: string}>
     */
    public static function textColors(): array
    {
        return [
            ['key' => 'white', 'hex' => '#FFFFFF', 'name' => __('loop.studio_white')],
            ['key' => 'ink', 'hex' => '#111114', 'name' => __('loop.studio_ink')],
            ['key' => 'lime', 'hex' => '#C8FF3D', 'name' => 'Lime'],
            ['key' => 'violet', 'hex' => '#5B2EFF', 'name' => 'Violet'],
            ['key' => 'coral', 'hex' => '#FF6B4A', 'name' => 'Coral'],
            ['key' => 'cream', 'hex' => '#F7F3EA', 'name' => 'Cream'],
        ];
    }

    /**
     * @return list<array{key: string, category: string, en: string, sw: string}>
     */
    public static function copies(?Business $business = null): array
    {
        $copies = [
            [
                'key' => 'now_on_loop',
                'category' => 'general',
                'en' => 'We’re on Loop — earn points on every purchase.',
                'sw' => 'Tuko kwenye Loop — pata pointi kila unaponunua.',
            ],
            [
                'key' => 'phone_is_id',
                'category' => 'general',
                'en' => 'Your phone is your identity.',
                'sw' => 'Simu yako ndiyo utambulisho wako.',
            ],
            [
                'key' => 'ask_at_till',
                'category' => 'general',
                'en' => 'Ask at the till to join Loop — it takes seconds.',
                'sw' => 'Uliza kwenye kaunta ujiunge na Loop — sekunde chache.',
            ],
            [
                'key' => 'bring_friends',
                'category' => 'general',
                'en' => 'Bring friends. Collect points. Redeem treats.',
                'sw' => 'Lete marafiki. Kusanya pointi. Komboa zawadi.',
            ],
            [
                'key' => 'rewards_waiting',
                'category' => 'offers',
                'en' => 'Points that unlock rewards at our shop.',
                'sw' => 'Pointi zinazofungua zawadi kwenye duka letu.',
            ],
        ];

        if ($business) {
            foreach ($business->rewards()->where('is_active', true)->orderBy('points_cost')->limit(4)->get() as $reward) {
                /** @var Reward $reward */
                $copies[] = [
                    'key' => 'offer_'.$reward->id,
                    'category' => 'offers',
                    'en' => self::offerCopyEn($reward),
                    'sw' => self::offerCopySw($reward),
                ];
            }

            foreach ($business->campaigns()->where('is_active', true)->latest()->limit(4)->get() as $campaign) {
                /** @var Campaign $campaign */
                $copies[] = [
                    'key' => 'campaign_'.$campaign->id,
                    'category' => 'campaigns',
                    'en' => self::campaignCopyEn($campaign, $business->currency),
                    'sw' => self::campaignCopySw($campaign, $business->currency),
                ];
            }
        }

        return $copies;
    }

    private static function offerCopyEn(Reward $reward): string
    {
        return 'Unlock '.$reward->name.' with '.$reward->points_cost.' points.';
    }

    private static function offerCopySw(Reward $reward): string
    {
        return 'Fungua '.$reward->name.' kwa pointi '.$reward->points_cost.'.';
    }

    private static function campaignCopyEn(Campaign $campaign, string $currency): string
    {
        return match ($campaign->type) {
            Campaign::TYPE_EARN, 'product_push' => sprintf(
                'Get %s points for every %s %s spent.',
                number_format((int) $campaign->points_per_step),
                $currency,
                number_format((int) $campaign->spend_step)
            ),
            Campaign::TYPE_BIRTHDAY => sprintf(
                'Get %s bonus points when you purchase on your birthday.',
                number_format((int) $campaign->bonus_points)
            ),
            Campaign::TYPE_WELCOME => sprintf(
                'Get %s welcome points when you join.',
                number_format((int) $campaign->bonus_points)
            ),
            Campaign::TYPE_STREAK => sprintf(
                'Get %s points after %s visits in a %s.',
                number_format((int) $campaign->bonus_points),
                (int) ($campaign->streak_target ?: 3),
                $campaign->streak_period ?: 'week'
            ),
            default => $campaign->displayName(),
        };
    }

    private static function campaignCopySw(Campaign $campaign, string $currency): string
    {
        return match ($campaign->type) {
            Campaign::TYPE_EARN, 'product_push' => sprintf(
                'Pata pointi %s kwa kila %s %s unazotumia.',
                number_format((int) $campaign->points_per_step),
                $currency,
                number_format((int) $campaign->spend_step)
            ),
            Campaign::TYPE_BIRTHDAY => sprintf(
                'Pata pointi za ziada %s unaponunua siku ya kuzaliwa.',
                number_format((int) $campaign->bonus_points)
            ),
            Campaign::TYPE_WELCOME => sprintf(
                'Pata pointi %s za karibu unapojiunga.',
                number_format((int) $campaign->bonus_points)
            ),
            Campaign::TYPE_STREAK => sprintf(
                'Pata pointi %s baada ya ziara %s kwa %s.',
                number_format((int) $campaign->bonus_points),
                (int) ($campaign->streak_target ?: 3),
                $campaign->streak_period === 'month' ? 'mwezi' : 'wiki'
            ),
            default => $campaign->displayName(),
        };
    }
}
