<?php

namespace App\Support;

class CampaignTemplates
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'everyday_earn' => [
                'name' => 'Everyday earn',
                'type' => 'earn',
                'description' => 'Steady points on every purchase — great default for most shops.',
                'spend_step' => 1000,
                'points_per_step' => 2,
                'bonus_points' => 0,
            ],
            'hundred_point_discount' => [
                'name' => '100 points → 5% off',
                'type' => 'earn',
                'description' => 'Customers aim for 100 points, then get 5% off when they buy.',
                'spend_step' => 1000,
                'points_per_step' => 2,
                'bonus_points' => 0,
                'reward' => [
                    'name' => '5% off anything',
                    'points_cost' => 100,
                    'reward_type' => 'percent_off',
                    'reward_value' => 5,
                ],
            ],
            'product_push' => [
                'name' => 'Featured product push',
                'type' => 'product_push',
                'description' => 'Extra points when staff mark a featured product on the sale.',
                'spend_step' => 1000,
                'points_per_step' => 2,
                'bonus_points' => 10,
            ],
            'visit_streak' => [
                'name' => 'Visit streak',
                'type' => 'streak',
                'description' => 'Bonus after several visits in a short window — classic retention.',
                'spend_step' => null,
                'points_per_step' => null,
                'bonus_points' => 30,
            ],
            'birthday_treat' => [
                'name' => 'Birthday treat',
                'type' => 'birthday',
                'description' => 'Bonus points on the customer’s birthday month.',
                'spend_step' => null,
                'points_per_step' => null,
                'bonus_points' => 50,
            ],
            'welcome_bonus' => [
                'name' => 'Welcome bonus',
                'type' => 'welcome',
                'description' => 'First-sale boost for new Loop members.',
                'spend_step' => null,
                'points_per_step' => null,
                'bonus_points' => 20,
            ],
        ];
    }
}
