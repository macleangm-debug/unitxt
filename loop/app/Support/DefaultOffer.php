<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Reward;

class DefaultOffer
{
    /**
     * Ensure every business has one evergreen default offer members can always chase.
     */
    public static function ensure(Business $business): Reward
    {
        $existing = $business->rewards()->where('is_default', true)->first();
        if ($existing) {
            if (! $existing->is_active) {
                $existing->update([
                    'is_active' => true,
                    'stock' => null,
                    'starts_at' => null,
                    'ends_at' => null,
                ]);
            }

            return $existing->fresh();
        }

        $promote = $business->rewards()->where('is_active', true)->orderBy('points_cost')->first();
        if ($promote) {
            $promote->update([
                'is_default' => true,
                'stock' => $promote->stock,
                'starts_at' => $promote->starts_at,
                'ends_at' => $promote->ends_at,
            ]);

            return $promote->fresh();
        }

        return $business->rewards()->create([
            'name' => __('loop.default_offer_name'),
            'description' => __('loop.default_offer_description'),
            'points_cost' => 100,
            'reward_type' => Reward::TYPE_PERCENT_OFF,
            'reward_value' => 5,
            'stock' => null,
            'max_redemptions_per_member' => null,
            'starts_at' => null,
            'ends_at' => null,
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
