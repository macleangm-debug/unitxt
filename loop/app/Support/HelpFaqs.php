<?php

namespace App\Support;

class HelpFaqs
{
    /**
     * @return list<array{key: string, title: string, items: list<array{q: string, a: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'how',
                'title' => __('loop.help_group_how'),
                'items' => [
                    ['q' => __('loop.help_how_q'), 'a' => __('loop.help_how_a')],
                    ['q' => __('loop.help_earn_q'), 'a' => __('loop.help_earn_a')],
                    ['q' => __('loop.help_reward_q'), 'a' => __('loop.help_reward_a')],
                    ['q' => __('loop.help_where_q'), 'a' => __('loop.help_where_a')],
                ],
            ],
            [
                'key' => 'account',
                'title' => __('loop.help_group_account'),
                'items' => [
                    ['q' => __('loop.help_points_q'), 'a' => __('loop.help_points_a')],
                    ['q' => __('loop.help_update_q'), 'a' => __('loop.help_update_a')],
                    ['q' => __('loop.help_privacy_q'), 'a' => __('loop.help_privacy_a')],
                ],
            ],
            [
                'key' => 'support',
                'title' => __('loop.help_group_support'),
                'items' => [
                    ['q' => __('loop.help_support_q'), 'a' => __('loop.help_support_a')],
                    ['q' => __('loop.member_faq_1_q'), 'a' => __('loop.member_faq_1_a')],
                ],
            ],
        ];
    }
}
