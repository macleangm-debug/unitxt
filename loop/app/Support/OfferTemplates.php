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
        ];
    }

    public static function typeStarter(string $type): ?array
    {
        return collect(self::typeStarters())->firstWhere('key', $type)
            ?? collect(self::typeStarters())->firstWhere('reward_type', $type);
    }

    /**
     * Editable onboarding templates — not fixed menu items.
     *
     * @return list<array<string, mixed>>
     */
    public static function forSector(string $sector = 'other'): array
    {
        // Sector kept for call-site compatibility; starters are universal and editable.
        unset($sector);

        return [
            self::localize('percent_5', [
                'points_cost' => 100,
                'reward_type' => 'percent_off',
                'reward_value' => 5,
            ]),
            self::localize('percent_10', [
                'points_cost' => 200,
                'reward_type' => 'percent_off',
                'reward_value' => 10,
            ]),
            self::localize('free_item', [
                'points_cost' => 100,
                'reward_type' => 'free_item',
                'reward_value' => 0,
            ]),
            self::localize('fixed_off', [
                'points_cost' => 150,
                'reward_type' => 'fixed_off',
                'reward_value' => 2000,
            ]),
        ];
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
        return [
            'key' => $key,
            'name' => __('loop.offer_templates.'.$key.'.name'),
            'description' => __('loop.offer_templates.'.$key.'.description'),
            'points_cost' => $item['points_cost'],
            'reward_type' => $item['reward_type'],
            'reward_value' => $item['reward_value'],
            'product_name' => null,
        ];
    }
}
