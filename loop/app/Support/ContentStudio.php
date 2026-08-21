<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Raffle;
use App\Models\Reward;

class ContentStudio
{
    /**
     * @return list<array{key: string, name: string, accent: string, from: string, to: string, ink: string}>
     */
    public static function designs(): array
    {
        return [
            ['key' => 'mint_card', 'name' => __('loop.studio_design_mint'), 'accent' => '#2DD4A8', 'from' => '#2DD4A8', 'to' => '#0F6B56', 'ink' => '#0B1F2A'],
            ['key' => 'ink_bold', 'name' => __('loop.studio_design_ink'), 'accent' => '#0B1F2A', 'from' => '#0B1F2A', 'to' => '#173445', 'ink' => '#FFFFFF'],
            ['key' => 'coral_pop', 'name' => __('loop.studio_design_coral'), 'accent' => '#FF6B4A', 'from' => '#FF6B4A', 'to' => '#ff8f75', 'ink' => '#0B1F2A'],
            ['key' => 'cream_soft', 'name' => __('loop.studio_design_cream'), 'accent' => '#F7F3EA', 'from' => '#F7F3EA', 'to' => '#E8DFC8', 'ink' => '#0B1F2A'],
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function topics(): array
    {
        return [
            ['key' => 'loop', 'label' => __('loop.studio_topic_loop')],
            ['key' => 'offer', 'label' => __('loop.studio_topic_offer')],
            ['key' => 'campaign', 'label' => __('loop.studio_topic_campaign')],
            ['key' => 'raffle', 'label' => __('loop.studio_topic_raffle')],
        ];
    }

    /**
     * @return list<array{key: string, en: string, sw: string, support_en: string, support_sw: string}>
     */
    public static function copies(): array
    {
        return [
            [
                'key' => 'now_on_loop',
                'en' => 'We’re on Loop — earn points every visit.',
                'sw' => 'Tuko kwenye Loop — pata pointi kila unapotembelea.',
                'support_en' => '',
                'support_sw' => '',
            ],
            [
                'key' => 'phone_is_id',
                'en' => 'Your phone is your loyalty card.',
                'sw' => 'Simu yako ndiyo kadi yako ya uaminifu.',
                'support_en' => '',
                'support_sw' => '',
            ],
            [
                'key' => 'ask_at_till',
                'en' => 'Ask at the till to join Loop — it takes seconds.',
                'sw' => 'Uliza kwenye kaunta ujiunge na Loop — sekunde chache.',
                'support_en' => '',
                'support_sw' => '',
            ],
            [
                'key' => 'rewards_waiting',
                'en' => 'Points that unlock real rewards at our shop.',
                'sw' => 'Pointi zinazofungua zawadi halisi kwenye duka letu.',
                'support_en' => '',
                'support_sw' => '',
            ],
            [
                'key' => 'bring_friends',
                'en' => 'Bring friends. Collect points. Redeem treats.',
                'sw' => 'Lete marafiki. Kusanya pointi. Komboa zawadi.',
                'support_en' => '',
                'support_sw' => '',
            ],
        ];
    }

    /**
     * Live copy grouped by topic. Reads existing Loop records — never creates them.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function copiesByTopic(Business $business): array
    {
        $loop = self::copies();
        $earn = $business->campaigns()->where('type', Campaign::TYPE_EARN)->where('is_active', true)->latest()->first();
        if ($earn && $earn->spend_step) {
            $amount = number_format((int) $earn->spend_step);
            $summary = $earn->ruleSummary($business->currency);
            array_unshift($loop, [
                'key' => 'points_from_earn',
                'en' => trans('loop.studio_copy_points', ['currency' => $business->currency, 'amount' => $amount], 'en'),
                'sw' => trans('loop.studio_copy_points', ['currency' => $business->currency, 'amount' => $amount], 'sw'),
                'support_en' => $summary,
                'support_sw' => $summary,
            ]);
        }

        $offers = $business->rewards()->where('is_active', true)->orderBy('points_cost')->get()
            ->map(function (Reward $reward) {
                $support = $reward->points_cost.' pts · '.$reward->label();

                return [
                    'key' => 'offer_'.$reward->id,
                    'source_id' => $reward->id,
                    'en' => trans('loop.studio_copy_offer', ['name' => $reward->name], 'en'),
                    'sw' => trans('loop.studio_copy_offer', ['name' => $reward->name], 'sw'),
                    'support_en' => $support,
                    'support_sw' => $support,
                ];
            })
            ->values()
            ->all();

        $campaigns = $business->campaigns()->where('is_active', true)->latest()->get()
            ->map(function (Campaign $campaign) use ($business) {
                $summary = $campaign->ruleSummary($business->currency);
                $amount = number_format((int) ($campaign->spend_step ?: 0));
                $isEarn = $campaign->type === Campaign::TYPE_EARN;

                return [
                    'key' => 'campaign_'.$campaign->id,
                    'source_id' => $campaign->id,
                    'en' => $isEarn
                        ? trans('loop.studio_copy_campaign_earn', ['currency' => $business->currency, 'amount' => $amount], 'en')
                        : $campaign->displayName(),
                    'sw' => $isEarn
                        ? trans('loop.studio_copy_campaign_earn', ['currency' => $business->currency, 'amount' => $amount], 'sw')
                        : $campaign->displayName(),
                    'support_en' => $summary,
                    'support_sw' => $summary,
                ];
            })
            ->values()
            ->all();

        $raffles = $business->raffles()
            ->whereIn('status', ['scheduled', 'live'])
            ->orderBy('draw_at')
            ->get()
            ->map(function (Raffle $raffle) {
                $when = $raffle->draw_at?->format('j M') ?? '';

                return [
                    'key' => 'raffle_'.$raffle->id,
                    'source_id' => $raffle->id,
                    'en' => trans('loop.studio_copy_raffle', ['prize' => $raffle->prize_name], 'en'),
                    'sw' => trans('loop.studio_copy_raffle', ['prize' => $raffle->prize_name], 'sw'),
                    'support_en' => trans('loop.studio_copy_raffle_support', [
                        'name' => $raffle->name,
                        'date' => $when,
                    ], 'en'),
                    'support_sw' => trans('loop.studio_copy_raffle_support', [
                        'name' => $raffle->name,
                        'date' => $when,
                    ], 'sw'),
                ];
            })
            ->values()
            ->all();

        return [
            'loop' => $loop,
            'offer' => $offers,
            'campaign' => $campaigns,
            'raffle' => $raffles,
        ];
    }
}
