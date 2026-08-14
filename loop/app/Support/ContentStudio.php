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
                'en' => 'Your phone is your ID card — not a loyalty card.',
                'sw' => 'Simu yako ndiyo kitambulisho chako — si kadi ya uaminifu.',
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
                'en' => 'Points that unlock real rewards at our shop.',
                'sw' => 'Pointi zinazofungua zawadi halisi kwenye duka letu.',
            ],
        ];

        if ($business) {
            foreach ($business->rewards()->where('is_active', true)->orderBy('points_cost')->limit(4)->get() as $reward) {
                /** @var Reward $reward */
                $copies[] = [
                    'key' => 'offer_'.$reward->id,
                    'category' => 'offers',
                    'en' => 'Get '.$reward->name.' after '.$reward->points_cost.' pts.',
                    'sw' => 'Pata '.$reward->name.' baada ya pointi '.$reward->points_cost.'.',
                ];
            }

            foreach ($business->campaigns()->where('is_active', true)->latest()->limit(3)->get() as $campaign) {
                /** @var Campaign $campaign */
                $copies[] = [
                    'key' => 'campaign_'.$campaign->id,
                    'category' => 'campaigns',
                    'en' => $campaign->displayName().' — '.$campaign->ruleSummary($business->currency),
                    'sw' => $campaign->displayName().' — '.$campaign->ruleSummary($business->currency),
                ];
            }
        }

        return $copies;
    }
}
