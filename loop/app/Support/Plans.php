<?php

namespace App\Support;

class Plans
{
    public const FREE = 'free';

    public const STARTER = 'starter';

    public const GROWTH = 'growth';

    public const SCALE = 'scale';

    /**
     * Launch pricing for Tanzania SMEs (TZS). Keep generous free tier for virality.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalog(): array
    {
        return [
            self::FREE => [
                'name' => 'Free',
                'tagline' => 'Start looping — no card needed.',
                'price_monthly' => 0,
                'currency' => 'TZS',
                'max_shops' => 1,
                'max_members' => 150,
                'sort_order' => 1,
                'features' => [
                    '1 shop',
                    'Up to 150 members',
                    'Campaigns & offers',
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
                'sort_order' => 2,
                'features' => [
                    '1 shop',
                    'Unlimited members',
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
                'sort_order' => 3,
                'features' => [
                    'Up to 5 shops',
                    'Unlimited members',
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
                'sort_order' => 4,
                'features' => [
                    'Unlimited shops',
                    'Dedicated success check-ins',
                    'Custom sector offer packs',
                    'Highest referral rewards',
                ],
            ],
        ];
    }

    /** Free months granted per qualified business referral. */
    public static function referralFreeMonths(): int
    {
        return 1;
    }

    /** Percent off next invoice when free months are not used (admin can choose). */
    public static function referralDiscountPercent(): int
    {
        return 50;
    }

    public static function trialDays(): int
    {
        return 30;
    }
}
