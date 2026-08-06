<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Shop;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitService
{
    public function __construct(
        private readonly MembershipService $memberships,
        private readonly PointsService $points,
    ) {}

    public function checkIn(User $customer, Shop $shop, string $method = 'code'): Visit
    {
        if (! $customer->isCustomer()) {
            throw ValidationException::withMessages([
                'customer' => 'Only customers can check in at shops.',
            ]);
        }

        $shop->loadMissing('business');
        $shop->refresh();

        if (! $shop->is_active || ! $shop->business?->is_active) {
            throw ValidationException::withMessages([
                'shop' => 'This shop is not currently accepting visits.',
            ]);
        }

        $campaign = $this->findBestCampaign($shop);

        if (! $campaign) {
            throw ValidationException::withMessages([
                'campaign' => 'No active campaign is available at this shop right now.',
            ]);
        }

        $todayVisits = Visit::query()
            ->where('customer_id', $customer->id)
            ->where('shop_id', $shop->id)
            ->where('campaign_id', $campaign->id)
            ->whereDate('created_at', today())
            ->count();

        if ($todayVisits >= $campaign->max_visits_per_day) {
            throw ValidationException::withMessages([
                'visit' => 'You have already reached today’s visit limit for this campaign.',
            ]);
        }

        return DB::transaction(function () use ($customer, $shop, $campaign, $method) {
            $membership = $this->memberships->join($shop->business, $customer);
            $pointsEarned = $campaign->pointsForVisit();

            $visit = Visit::create([
                'business_id' => $shop->business_id,
                'shop_id' => $shop->id,
                'customer_id' => $customer->id,
                'campaign_id' => $campaign->id,
                'membership_id' => $membership->id,
                'points_earned' => $pointsEarned,
                'check_in_method' => $method,
            ]);

            $this->points->earn(
                $membership,
                $pointsEarned,
                $visit,
                "Visited {$shop->name} · {$campaign->name}"
            );

            return $visit->fresh(['shop', 'campaign', 'business', 'membership']);
        });
    }

    private function findBestCampaign(Shop $shop): ?Campaign
    {
        return Campaign::query()
            ->active()
            ->where('business_id', $shop->business_id)
            ->where(function ($query) use ($shop) {
                $query->whereDoesntHave('shops')
                    ->orWhereHas('shops', fn ($shops) => $shops->where('shops.id', $shop->id));
            })
            ->orderByDesc('points_per_visit')
            ->orderByDesc('bonus_points')
            ->first();
    }
}
