<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Campaign;

class StarterLoyalty
{
    public const VISITS_TO_REWARD = 10;

    public const POINTS_PER_VISIT = 1;

    /**
     * Usual ticket sizes a shop owner can recognize without doing loyalty math.
     *
     * @return list<int>
     */
    public static function spendChoices(): array
    {
        return [5000, 10000, 20000];
    }

    /**
     * One starting earn rule + one free-item reward from a typical sale.
     *
     * @return array{spend_step: int, points_per_step: int, points_cost: int, visits: int, campaign_name: string, reward_name: string, product_name: string, reward_type: string}
     */
    public static function propose(int $typicalSpend, Business $business): array
    {
        $spend = max(1, $typicalSpend);
        $item = self::productName($business->sector ?: 'other');

        return [
            'spend_step' => $spend,
            'points_per_step' => self::POINTS_PER_VISIT,
            'points_cost' => self::VISITS_TO_REWARD * self::POINTS_PER_VISIT,
            'visits' => self::VISITS_TO_REWARD,
            'campaign_name' => trim($business->name.' '.__('loop.name_idea_points')),
            'reward_name' => __('loop.free_item_named', ['item' => $item]),
            'product_name' => $item,
            'reward_type' => 'free_item',
        ];
    }

    /**
     * @return array{spend_step: int, points_per_step: int, points_cost: int, visits: int, campaign_name: string, reward_name: string, product_name: string, reward_type: string}
     */
    public static function fromCampaign(Campaign $campaign, Business $business): array
    {
        return self::propose((int) ($campaign->spend_step ?: 5000), $business);
    }

    public static function productName(string $sector): string
    {
        $key = match ($sector) {
            'coffee' => 'coffee',
            'bakery' => 'pastry',
            'restaurants', 'fast_food' => 'meal',
            'hospitality', 'hotel' => 'breakfast',
            default => 'item',
        };

        return __('loop.offer_templates.products.'.$key);
    }
}
