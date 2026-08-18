<?php

namespace App\Services;

use App\Models\Affiliate;
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

        $owner = $business->owner;
        if (! $owner) {
            return 0;
        }

        $day = ($day ?? now())->copy()->startOfDay();
        $created = 0;

        $created += $this->pushInsights($owner, $business, $day);

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
            'when' => $business->campaigns()->where('type', 'earn')->where('is_active', true)->doesntExist(),
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

        $tip = app(SalesTipService::class)->tipFor($business);
        $created += $this->push($owner, $business, 'sales_tip', $business->sector ?: 'default', $day, [
            'title_key' => $tip['title_key'],
            'body_key' => $tip['body_key'],
            'cta_key' => $tip['cta_key'],
            'url' => $tip['url'],
            'tone' => 'mint',
            'when' => true,
        ]);

        $created += $this->push($owner, $business, 'daily_hello', 'hello', $day, [
            'title_key' => 'loop.notif_daily_hello_title',
            'body_key' => 'loop.notif_daily_hello_body',
            'params' => [
                'business' => $business->name,
                'customers' => $business->uniqueMemberCount(),
                'members' => $business->uniqueMemberCount(),
            ],
            'cta_key' => 'loop.start_selling',
            'url' => route('till.index'),
            'tone' => 'mint',
            'when' => true,
        ]);

        $created += $this->ensureMinimum($owner, $business, $day, 'owner', [
            [
                'type' => 'daily_hello',
                'dedupe' => 'hello_fill',
                'title_key' => 'loop.notif_daily_hello_title',
                'body_key' => 'loop.notif_daily_hello_body',
                'params' => ['business' => $business->name, 'customers' => $business->uniqueMemberCount(), 'members' => $business->uniqueMemberCount()],
                'cta_key' => 'loop.start_selling',
                'url' => route('till.index'),
                'tone' => 'mint',
            ],
        ]);

        return $created;
    }

    public function generateForCustomer(User $customer, ?Carbon $day = null): int
    {
        if (! FeatureFlags::enabled('member_daily_digest') || ! $customer->isCustomer()) {
            return 0;
        }
        if (! NotificationSettings::settings()['customer_in_app']) {
            return 0;
        }

        $day = ($day ?? now())->copy()->startOfDay();
        $memberships = Membership::query()
            ->with([
                'business.rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost'),
            ])
            ->where('customer_id', $customer->id)
            ->get();
        $membership = $memberships->sortByDesc('id')->first();
        $business = $membership?->business;
        $created = 0;

        $created += $this->push($customer, $business, 'member_hello', 'hello', $day, [
            'title_key' => 'loop.notif_member_hello_title',
            'body_key' => 'loop.notif_member_hello_body',
            'params' => [
                'name' => $customer->first_name ?: $customer->name,
                'points' => (int) $memberships->sum('points_balance'),
            ],
            'cta_key' => 'loop.discover',
            'url' => route('discover'),
            'tone' => 'mint',
            'when' => true,
            'audience' => 'customer',
        ]);

        $ready = null;
        foreach ($memberships as $row) {
            $reward = $row->nearestReadyReward();
            if ($reward) {
                $ready = [$row, $reward];
                break;
            }
        }

        if ($ready) {
            [$readyMembership, $reward] = $ready;
            $created += $this->push($customer, $readyMembership->business, 'member_redeem_ready', 'redeem_'.$readyMembership->business_id.'_'.$reward->id, $day, [
                'title_key' => 'loop.notif_member_redeem_title',
                'body_key' => 'loop.notif_member_redeem_body',
                'params' => [
                    'shop' => $readyMembership->business->name,
                    'offer' => $reward->name,
                ],
                'cta_key' => 'loop.see_rewards',
                'url' => route('memberships.show', $readyMembership->business),
                'tone' => 'mint',
                'when' => true,
                'audience' => 'customer',
            ]);
        } else {
            $created += $this->push($customer, $business, 'member_offers', 'offers', $day, [
                'title_key' => 'loop.notif_member_offers_title',
                'body_key' => 'loop.notif_member_offers_body',
                'cta_key' => 'loop.wallets',
                'url' => route('memberships.index'),
                'tone' => 'violet',
                'when' => true,
                'audience' => 'customer',
            ]);
        }

        return $created;
    }

    public function generateForAffiliate(User $affiliateUser, ?Carbon $day = null): int
    {
        if (! FeatureFlags::enabled('affiliate_daily_digest') || ! $affiliateUser->isAffiliate()) {
            return 0;
        }
        if (! NotificationSettings::settings()['affiliate_in_app']) {
            return 0;
        }

        $day = ($day ?? now())->copy()->startOfDay();
        $profile = Affiliate::query()->where('user_id', $affiliateUser->id)->first();
        $created = 0;

        $created += $this->push($affiliateUser, null, 'affiliate_share', 'share', $day, [
            'title_key' => 'loop.notif_affiliate_share_title',
            'body_key' => 'loop.notif_affiliate_share_body',
            'params' => ['code' => $profile?->promo_code ?? ''],
            'cta_key' => 'loop.affiliate_nav_share',
            'url' => route('affiliate.dashboard').'#share',
            'tone' => 'mint',
            'when' => true,
            'audience' => 'affiliate',
        ]);

        $created += $this->push($affiliateUser, null, 'affiliate_tips', 'tips', $day, [
            'title_key' => 'loop.notif_affiliate_tips_title',
            'body_key' => 'loop.notif_affiliate_tips_body',
            'cta_key' => 'loop.affiliate_nav_referrals',
            'url' => route('affiliate.dashboard').'#referrals',
            'tone' => 'violet',
            'when' => true,
            'audience' => 'affiliate',
        ]);

        return $created;
    }

    public function ensureTodayForOwner(User $owner): void
    {
        $this->ensureTodayForUser($owner);
    }

    public function ensureTodayForUser(User $user): void
    {
        $today = now()->toDateString();
        $count = InAppNotification::query()
            ->where('user_id', $user->id)
            ->whereDate('for_date', $today)
            ->count();

        if ($count >= 2) {
            return;
        }

        if ($user->isOwner() && FeatureFlags::enabled('owner_daily_digest')) {
            $business = $user->ownedBusiness;
            if ($business) {
                $this->generateForBusiness($business);
            }
        }

        if ($user->isCustomer()) {
            $this->generateForCustomer($user);
        }

        if ($user->isAffiliate()) {
            $this->generateForAffiliate($user);
        }
    }

    private function pushInsights(User $owner, Business $business, Carbon $day): int
    {
        $created = 0;
        foreach (app(BusinessInsightService::class)->notificationSpecs($business) as $spec) {
            $created += $this->push($owner, $business, 'insight_'.$spec['key'], $spec['key'], $day, [
                'title_key' => $spec['title_key'],
                'body_key' => $spec['body_key'],
                'params' => $spec['params'] ?? [],
                'cta_key' => $spec['cta_key'],
                'url' => $spec['url'],
                'tone' => $spec['tone'] ?? 'mint',
                'when' => true,
            ]);
        }

        return $created;
    }

    /**
     * @param  list<array<string, mixed>>  $fallbacks
     */
    private function ensureMinimum(User $user, ?Business $business, Carbon $day, string $audience, array $fallbacks): int
    {
        $count = InAppNotification::query()
            ->where('user_id', $user->id)
            ->whereDate('for_date', $day->toDateString())
            ->count();
        $created = 0;

        foreach ($fallbacks as $row) {
            if ($count + $created >= 2) {
                break;
            }
            $created += $this->push($user, $business, $row['type'], $row['dedupe'], $day, [
                ...$row,
                'when' => true,
                'audience' => $audience,
            ]);
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function push(User $user, ?Business $business, string $type, string $dedupe, Carbon $day, array $data): int
    {
        if (empty($data['when'])) {
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
            'business_id' => $business?->id,
            'audience' => $data['audience'] ?? 'owner',
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
