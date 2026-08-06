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
                'description' => 'Proven for cafés and retail: steady points on every purchase.',
                'spend_step' => 1000,
                'points_per_step' => 2,
                'bonus_points' => 0,
            ],
            'hundred_point_discount' => [
                'name' => '100 points → 5% off',
                'type' => 'earn',
                'description' => 'Customers aim for 100 points, then get 5% off at the till.',
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
            'birthday_treat' => [
                'name' => 'Birthday treat',
                'type' => 'birthday',
                'description' => 'Bonus points during the customer’s birthday week.',
                'spend_step' => null,
                'points_per_step' => null,
                'bonus_points' => 50,
            ],
            'welcome_bonus' => [
                'name' => 'Welcome bonus',
                'type' => 'welcome',
                'description' => 'First-visit boost to get new Loop members hooked.',
                'spend_step' => null,
                'points_per_step' => null,
                'bonus_points' => 20,
            ],
        ];
    }
}
