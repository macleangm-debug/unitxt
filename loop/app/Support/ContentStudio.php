<?php

namespace App\Support;

class ContentStudio
{
    /**
     * @return list<array{key: string, name: string, accent: string}>
     */
    public static function designs(): array
    {
        return [
            ['key' => 'mint_card', 'name' => __('loop.studio_design_mint'), 'accent' => '#2DD4A8'],
            ['key' => 'ink_bold', 'name' => __('loop.studio_design_ink'), 'accent' => '#0B1F2A'],
            ['key' => 'coral_pop', 'name' => __('loop.studio_design_coral'), 'accent' => '#FF6B4A'],
            ['key' => 'cream_soft', 'name' => __('loop.studio_design_cream'), 'accent' => '#F7F3EA'],
        ];
    }

    /**
     * @return list<array{key: string, en: string, sw: string}>
     */
    public static function copies(): array
    {
        return [
            [
                'key' => 'now_on_loop',
                'en' => 'We’re on Loop — earn points every visit.',
                'sw' => 'Tuko kwenye Loop — pata pointi kila unapotembelea.',
            ],
            [
                'key' => 'phone_is_id',
                'en' => 'Your phone is your loyalty card.',
                'sw' => 'Simu yako ndiyo kadi yako ya uaminifu.',
            ],
            [
                'key' => 'ask_at_till',
                'en' => 'Ask at the till to join Loop — it takes seconds.',
                'sw' => 'Uliza kwenye kaunta ujiunge na Loop — sekunde chache.',
            ],
            [
                'key' => 'rewards_waiting',
                'en' => 'Points that unlock real rewards at our shop.',
                'sw' => 'Pointi zinazofungua zawadi halisi kwenye duka letu.',
            ],
            [
                'key' => 'bring_friends',
                'en' => 'Bring friends. Collect points. Redeem treats.',
                'sw' => 'Lete marafiki. Kusanya pointi. Komboa zawadi.',
            ],
        ];
    }
}
