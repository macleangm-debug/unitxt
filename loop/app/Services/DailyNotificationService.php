<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\InAppNotification;
use App\Models\Membership;
use App\Models\User;
use App\Models\Visit;
use App\Support\FeatureFlags;
use App\Support\NotificationSettings;
use Illuminate\Support\Carbon;

class DailyNotificationService
{
    /**
     * Generate today's owner digest notifications for one business (idempotent).
     *
     * @return int Number of notifications created
     */
    public function generateForBusiness(Business $business, ?Carbon $day = null): int
    {
        if (! FeatureFlags::enabled('owner_daily_digest')) {
            return 0;
        }

        if (! NotificationSettings::enabled('owner', 'in_app')) {
            return 0;
        }

        $owner = $business->owner;
        if (! $owner) {
            return 0;
        }

        $day = ($day ?? now())->copy()->startOfDay();
        $created = 0;

        $created += $this->push($owner, $business, 'setup_offers', 'need_offers', $day, [
            'title_key' => 'loop.notif_need_offers_title',
            'body_key' => 'loop.notif_need_offers_body',
            'cta_key' => 'loop.add_offer',
            'url' => route('rewards.create'),
            'tone' => 'ink',
            'when' => $business->rewards()->where('is_active', true)->doesntExist(),
        ]);

        $created += $this->push($owner, $business, 'setup_campaign', 'need_earn', $day, [
            'title_key' => 'loop.notif_need_earn_title',
            'body_key' => 'loop.notif_need_earn_body',
            'cta_key' => 'loop.view_campaigns',
            'url' => route('campaigns.create'),
            'tone' => 'ink',
            'when' => $business->campaigns()->whereIn('type', ['earn', 'product_push'])->where('is_active', true)->doesntExist(),
        ]);

        if (FeatureFlags::enabled('birthday_campaigns')) {
            $hasBirthday = $business->campaigns()->where('type', Campaign::TYPE_BIRTHDAY)->where('is_active', true)->exists();
            $created += $this->push($owner, $business, 'coach_birthday', 'no_birthday', $day, [
                'title_key' => 'loop.notif_coach_birthday_title',
                'body_key' => 'loop.notif_coach_birthday_body',
                'cta_key' => 'loop.view_campaigns',
                'url' => route('campaigns.index'),
                'tone' => 'mint',
                'when' => ! $hasBirthday && $business->uniqueMemberCount() >= 5,
            ]);
        }

        if (FeatureFlags::enabled('premium_clients')) {
            $top = $this->topCustomer($business, $day->copy()->subDays(7), $day);
            if ($top) {
                $created += $this->push($owner, $business, 'premium_client', 'top_'.$top['customer_id'], $day, [
                    'title_key' => 'loop.notif_premium_client_title',
                    'body_key' => 'loop.notif_premium_client_body',
                    'params' => [
                        'name' => $top['name'],
                        'visits' => $top['visits'],
                        'spend' => number_format($top['spend'], 0),
                        'currency' => $business->currency,
                    ],
                    'cta_key' => 'loop.view_customers',
                    'url' => route('customers.index'),
                    'tone' => 'mint',
                    'when' => true,
                ]);
            }
        }

        if (FeatureFlags::enabled('customer_unlock_hints')) {
            $near = $this->customerNearUnlock($business);
            if ($near) {
                $created += $this->push($owner, $business, 'unlock_hint', 'near_'.$near['customer_id'].'_'.$near['offer_id'], $day, [
                    'title_key' => 'loop.notif_unlock_hint_title',
                    'body_key' => 'loop.notif_unlock_hint_body',
                    'params' => [
                        'name' => $near['name'],
                        'points' => $near['needed'],
                        'offer' => $near['offer'],
                        'shop' => $near['shop'],
                    ],
                    'cta_key' => 'loop.view_customers',
                    'url' => route('customers.index'),
                    'tone' => 'mint',
                    'when' => true,
                ]);
            }
        }

        $quietDays = $this->quietDays($business, $day);
        $created += $this->push($owner, $business, 'quiet_till', 'quiet_'.$quietDays, $day, [
            'title_key' => 'loop.notif_quiet_title',
            'body_key' => 'loop.notif_quiet_body',
            'params' => ['days' => $quietDays],
            'cta_key' => 'loop.view_campaigns',
            'url' => route('campaigns.index'),
            'tone' => 'coral',
            'when' => $quietDays >= 3,
        ]);

        $thisWeek = Visit::query()
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $day->copy()->startOfWeek())
            ->count();
        $lastWeek = Visit::query()
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [
                $day->copy()->subWeek()->startOfWeek(),
                $day->copy()->subWeek()->endOfWeek(),
            ])
            ->count();

        if ($lastWeek >= 3 && $thisWeek > $lastWeek) {
            $pct = (int) round((($thisWeek - $lastWeek) / max(1, $lastWeek)) * 100);
            $created += $this->push($owner, $business, 'sales_up', 'up_'.$pct, $day, [
                'title_key' => 'loop.notif_sales_up_title',
                'body_key' => 'loop.notif_sales_up_body',
                'params' => ['pct' => $pct],
                'cta_key' => 'loop.view_all',
                'url' => route('transactions.index'),
                'tone' => 'mint',
                'when' => true,
            ]);
        }

        $created += $this->push($owner, $business, 'daily_hello', 'hello', $day, [
            'title_key' => 'loop.notif_daily_hello_title',
            'body_key' => 'loop.notif_daily_hello_body',
            'params' => [
                'business' => $business->name,
                'customers' => $business->uniqueMemberCount(),
            ],
            'cta_key' => 'loop.start_selling',
            'url' => route('till.index'),
            'tone' => 'mint',
            'when' => true,
        ]);

        return $created;
    }

    public function ensureTodayForOwner(User $owner): void
    {
        $business = $owner->ownedBusiness;
        if (
            ! $business
            || ! FeatureFlags::enabled('owner_daily_digest')
            || ! NotificationSettings::enabled('owner', 'in_app')
        ) {
            return;
        }

        $today = now()->toDateString();
        $exists = InAppNotification::query()
            ->where('user_id', $owner->id)
            ->whereDate('for_date', $today)
            ->exists();

        if (! $exists) {
            $this->generateForBusiness($business);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function push(User $user, Business $business, string $type, string $dedupe, Carbon $day, array $data): int
    {
        if (empty($data['when'])) {
            return 0;
        }

        if (! NotificationSettings::enabled('owner', 'in_app')) {
            return 0;
        }

        $existing = InAppNotification::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('dedupe_key', $dedupe)
            ->whereDate('for_date', $day->toDateString())
            ->exists();

        if ($existing) {
            return 0;
        }

        InAppNotification::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'audience' => 'owner',
            'type' => $type,
            'dedupe_key' => $dedupe,
            'title_key' => $data['title_key'],
            'body_key' => $data['body_key'],
            'params' => $data['params'] ?? [],
            'cta_key' => $data['cta_key'] ?? null,
            'url' => $data['url'] ?? null,
            'tone' => $data['tone'] ?? 'mint',
            'for_date' => $day->toDateString(),
        ]);

        return 1;
    }

    /**
     * @return array{customer_id: int, name: string, visits: int, spend: float}|null
     */
    private function topCustomer(Business $business, Carbon $from, Carbon $to): ?array
    {
        $row = Visit::query()
            ->selectRaw('customer_id, COUNT(*) as visits, SUM(amount_spent) as spend')
            ->where('business_id', $business->id)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('customer_id')
            ->orderByDesc('visits')
            ->first();

        if (! $row || (int) $row->visits < 2) {
            return null;
        }

        $customer = User::query()->find($row->customer_id);
        if (! $customer) {
            return null;
        }

        return [
            'customer_id' => (int) $row->customer_id,
            'name' => $customer->name,
            'visits' => (int) $row->visits,
            'spend' => (float) $row->spend,
        ];
    }

    /**
     * @return array{customer_id: int, name: string, needed: int, offer: string, offer_id: int, shop: string}|null
     */
    private function customerNearUnlock(Business $business): ?array
    {
        $offers = $business->rewards()->where('is_active', true)->orderBy('points_cost')->get();
        if ($offers->isEmpty()) {
            return null;
        }

        $memberships = Membership::query()
            ->with(['customer', 'shop'])
            ->where('business_id', $business->id)
            ->where('points_balance', '>', 0)
            ->orderByDesc('points_balance')
            ->limit(40)
            ->get();

        foreach ($memberships as $membership) {
            foreach ($offers as $offer) {
                $needed = $offer->points_cost - $membership->points_balance;
                if ($needed >= 1 && $needed <= 20) {
                    return [
                        'customer_id' => $membership->customer_id,
                        'name' => $membership->customer?->name ?? __('loop.customer'),
                        'needed' => $needed,
                        'offer' => $offer->name,
                        'offer_id' => $offer->id,
                        'shop' => $membership->shop?->name ?? $business->name,
                    ];
                }
            }
        }

        return null;
    }

    private function quietDays(Business $business, Carbon $day): int
    {
        $last = Visit::query()
            ->where('business_id', $business->id)
            ->latest('created_at')
            ->value('created_at');

        if (! $last) {
            return 0;
        }

        return (int) Carbon::parse($last)->startOfDay()->diffInDays($day);
    }
}
