<?php

namespace App\Support;

use App\Models\Campaign;

class OfferTemplates
{
    /**
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
            'free_coffee_100' => [
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
    private static function localize(string $key, array $item): array
    {
        return [
            'key' => $key,
            'name' => __('loop.offer_templates.'.$key.'.name'),
            'description' => __('loop.offer_templates.'.$key.'.description'),
            'points_cost' => $item['points_cost'],
            'reward_type' => $item['reward_type'],
            'reward_value' => $item['reward_value'],
            'product_name' => isset($item['product_name_key'])
                ? __('loop.offer_templates.products.'.$item['product_name_key'])
                : null,
        ];
    }
}
