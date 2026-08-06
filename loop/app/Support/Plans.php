<?php

namespace App\Support;

class Plans
{
    public const FREE = 'free';

    public const STARTER = 'starter';

    public const GROWTH = 'growth';

    public const SCALE = 'scale';

    /**
     * Launch pricing for Tanzania SMEs (TZS).
     * Free = one physical location + member/visit caps so "one account, many branches" hits a wall.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalog(): array
    {
        return [
            self::FREE => [
                'name' => 'Free',
                'tagline' => 'One physical shop — start looping, no card needed.',
                'price_monthly' => 0,
                'currency' => 'TZS',
                'max_shops' => 1,
                'max_members' => 150,
                'max_monthly_visits' => 300,
                'sort_order' => 1,
                'features' => [
                    '1 physical shop location',
                    'Up to 150 members',
                    'Up to 300 sales / month',
                    'Till + Discover listing',
                ],
            ],
            self::STARTER => [
                'name' => 'Starter',
                'tagline' => 'For a busy single location.',
                'price_monthly' => 25000,
                'currency' => 'TZS',
                'max_shops' => 1,
                'max_members' => null,
                'max_monthly_visits' => null,
                'sort_order' => 2,
                'features' => [
                    '1 physical shop',
                    'Unlimited members & sales',
                    'Priority support',
                    'Remove Loop branding on receipts',
                ],
            ],
            self::GROWTH => [
                'name' => 'Growth',
                'tagline' => 'Multi-branch brands that want to scale.',
                'price_monthly' => 60000,
                'currency' => 'TZS',
                'max_shops' => 5,
                'max_members' => null,
                'max_monthly_visits' => null,
                'sort_order' => 3,
                'features' => [
                    'Up to 5 shop locations',
                    'Unlimited members & sales',
                    'Referral rewards unlocked',
                    'Campaign templates + analytics',
                ],
            ],
            self::SCALE => [
                'name' => 'Scale',
                'tagline' => 'City-wide chains and franchise groups.',
                'price_monthly' => 120000,
                'currency' => 'TZS',
                'max_shops' => null,
                'max_members' => null,
                'max_monthly_visits' => null,
                'sort_order' => 4,
                'features' => [
                    'Unlimited shop locations',
                    'Dedicated success check-ins',
                    'Custom sector offer packs',
                    'Highest referral rewards',
                ],
            ],
        ];
    }

    public static function trialDays(): int
    {
        return 30;
    }
}
