<?php

namespace App\Support;

use App\Models\Campaign;

class OfferTemplates
{
    /**
     * Type-first starters — these drive the add-offer flow.
     *
     * @return list<array<string, mixed>>
     */
    public static function typeStarters(): array
    {
        return [
            self::localizeType('free_item', [
                'reward_type' => 'free_item',
                'points_cost' => 100,
                'reward_value' => 0,
                'default_name' => __('loop.offer_type_free_name'),
                'product_name' => null,
            ]),
            self::localizeType('percent_off', [
                'reward_type' => 'percent_off',
                'points_cost' => 100,
                'reward_value' => 5,
                'default_name' => __('loop.offer_type_percent_name', ['value' => 5]),
                'product_name' => null,
            ]),
            self::localizeType('fixed_off', [
                'reward_type' => 'fixed_off',
                'points_cost' => 150,
                'reward_value' => 2000,
                'default_name' => __('loop.offer_type_fixed_name'),
                'product_name' => null,
            ]),
            self::localizeType('bogo', [
                'reward_type' => 'custom',
                'points_cost' => 200,
                'reward_value' => 0,
                'default_name' => __('loop.offer_type_bogo_name'),
                'product_name' => null,
                'description' => __('loop.offer_type_bogo_desc'),
            ]),
            self::localizeType('upgrade', [
                'reward_type' => 'custom',
                'points_cost' => 80,
                'reward_value' => 0,
                'default_name' => __('loop.offer_type_upgrade_name'),
                'product_name' => null,
                'description' => __('loop.offer_type_upgrade_desc'),
            ]),
            self::localizeType('custom', [
                'reward_type' => 'custom',
                'points_cost' => 100,
                'reward_value' => 0,
                'default_name' => __('loop.offer_type_custom_name'),
                'product_name' => null,
            ]),
        ];
    }

    public static function typeStarter(string $type): ?array
    {
        return collect(self::typeStarters())->firstWhere('key', $type)
            ?? collect(self::typeStarters())->firstWhere('reward_type', $type);
    }

    /**
     * Sector-flavoured name ideas (optional) after a type is chosen.
     *
     * @return list<array<string, mixed>>
     */
    public static function forSector(string $sector): array
    {
        $catalog = [
            'percent_5_100' => [
                'points_cost' => 100,
                'reward_type' => 'percent_off',
                'reward_value' => 5,
                'sectors' => ['*'],
            ],
            'percent_10_200' => [
                'points_cost' => 200,
                'reward_type' => 'percent_off',
                'reward_value' => 10,
                'sectors' => ['*'],
            ],
            'free_item_100' => [
                'points_cost' => 100,
                'reward_type' => 'free_item',
                'reward_value' => 0,
                'product_name_key' => 'item',
                'sectors' => ['*'],
            ],
            'free_item_coffee' => [
                'points_cost' => 100,
                'reward_type' => 'free_item',
                'reward_value' => 0,
                'product_name_key' => 'coffee',
                'sectors' => ['coffee', 'restaurants', 'fast_food', 'hospitality'],
            ],
            'free_pastry_150' => [
                'points_cost' => 150,
                'reward_type' => 'free_item',
                'reward_value' => 0,
                'product_name_key' => 'pastry',
                'sectors' => ['coffee'],
            ],
            'free_appetizer_150' => [
                'points_cost' => 150,
                'reward_type' => 'free_item',
                'reward_value' => 0,
                'product_name_key' => 'appetizer',
                'sectors' => ['restaurants', 'fast_food'],
            ],
            'free_meal_500' => [
                'points_cost' => 500,
                'reward_type' => 'free_item',
                'reward_value' => 0,
                'product_name_key' => 'meal',
                'sectors' => ['restaurants', 'fast_food', 'hospitality'],
            ],
            'half_meal_400' => [
                'points_cost' => 400,
                'reward_type' => 'percent_off',
                'reward_value' => 50,
                'sectors' => ['restaurants', 'fast_food'],
            ],
            'half_night_800' => [
                'points_cost' => 800,
                'reward_type' => 'percent_off',
                'reward_value' => 50,
                'product_name_key' => 'night',
                'sectors' => ['hospitality'],
            ],
            'free_breakfast_200' => [
                'points_cost' => 200,
                'reward_type' => 'free_item',
                'reward_value' => 0,
                'product_name_key' => 'breakfast',
                'sectors' => ['hospitality'],
            ],
            'fashion_10_200' => [
                'points_cost' => 200,
                'reward_type' => 'percent_off',
                'reward_value' => 10,
                'sectors' => ['fashion', 'beauty', 'retail'],
            ],
            // Legacy key still used by older onboarding seeds/tests
            'free_coffee_100' => [
                'points_cost' => 100,
                'reward_type' => 'free_item',
                'reward_value' => 0,
                'product_name_key' => 'coffee',
                'sectors' => ['coffee', 'restaurants', 'fast_food', 'hospitality'],
            ],
        ];

        $out = [];
        foreach ($catalog as $key => $item) {
            $sectors = $item['sectors'];
            if (! in_array('*', $sectors, true) && ! in_array($sector, $sectors, true)) {
                continue;
            }
            $out[] = self::localize($key, $item);
        }

        return $out;
    }

    /**
     * Approximate spend needed to unlock an offer given an earn campaign.
     */
    public static function spendToUnlock(?Campaign $earn, int $pointsCost, string $currency = 'TZS'): ?string
    {
        if (! $earn || ! $earn->spend_step || ! $earn->points_per_step) {
            return null;
        }

        $spend = (int) ceil($pointsCost / $earn->points_per_step) * $earn->spend_step;

        return __('loop.spend_to_unlock', [
            'currency' => $currency,
            'amount' => number_format($spend),
            'points' => $pointsCost,
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function localizeType(string $key, array $item): array
    {
        return [
            'key' => $key,
            'name' => __('loop.offer_type_'.$key.'_title'),
            'description' => $item['description'] ?? __('loop.offer_type_'.$key.'_body'),
            'points_cost' => $item['points_cost'],
            'reward_type' => $item['reward_type'],
            'reward_value' => $item['reward_value'],
            'default_name' => $item['default_name'],
            'product_name' => $item['product_name'],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function localize(string $key, array $item): array
    {
        $product = isset($item['product_name_key'])
            ? __('loop.offer_templates.products.'.$item['product_name_key'])
            : null;

        $name = __('loop.offer_templates.'.$key.'.name');
        if ($key === 'free_item_100') {
            $name = __('loop.free_item');
        }

        $description = __('loop.offer_templates.'.$key.'.description');

        return [
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'points_cost' => $item['points_cost'],
            'reward_type' => $item['reward_type'],
            'reward_value' => $item['reward_value'],
            'product_name' => $product,
        ];
    }
}
