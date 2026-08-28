<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\Membership;
use App\Models\Visit;

class OwnerPulseService
{
    /**
     * Conversational owner Home: what happened, what is happening, what needs me, what next.
     *
     * @return array<string, mixed>
     */
    public function for(Business $business): array
    {
        $todayVisits = $business->visits()->whereDate('created_at', today())->count();
        $todayIds = $business->visits()->whereDate('created_at', today())->pluck('customer_id')->unique();
        $todayReturning = $todayIds->isEmpty()
            ? 0
            : Visit::query()
                ->where('business_id', $business->id)
                ->whereIn('customer_id', $todayIds)
                ->whereDate('created_at', '<', today())
                ->pluck('customer_id')
                ->unique()
                ->count();

        $memberships = $business->memberships()->with(['business.rewards' => fn ($q) => $q->where('is_active', true)])->get();
        $redeemable = $memberships->filter(fn (Membership $m) => $m->availableRewards()->isNotEmpty())->count();
        $closeToReward = $memberships->filter(function (Membership $m) {
            $next = $m->nextReward();
            if (! $next) {
                return false;
            }
            $needed = $m->progressTo($next)['needed'] ?? 0;

            return $needed > 0 && $needed <= 20;
        })->count();

        $monthStart = now()->startOfMonth();
        $returningIds = Visit::query()
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $monthStart)
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('customer_id');
        $returningMonth = $returningIds->count();
        $returningSpend = $returningIds->isEmpty()
            ? 0.0
            : (float) $business->visits()
                ->where('created_at', '>=', $monthStart)
                ->whereIn('customer_id', $returningIds)
                ->sum('amount_spent');

        $endingCampaign = $business->campaigns()
            ->active()
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '>=', today())
            ->whereDate('ends_at', '<=', today()->addDays(5))
            ->orderBy('ends_at')
            ->first();

        $memberCount = $business->uniqueMemberCount();
        $newThisWeek = $business->memberships()->where('created_at', '>=', now()->subDays(7))->count();
        $milestone = $this->milestone($memberCount, $newThisWeek);

        $needs = $this->needs($business, $endingCampaign);
        $next = $this->nextAction($business, $redeemable, $closeToReward, $needs);
        $suggestion = $this->suggestion($business, $closeToReward, $returningMonth, $redeemable);
        $momentum = app(LoopAccess::class)->momentum($business);
        $prompts = $this->prompts($business, $suggestion, $returningMonth, $returningSpend, $momentum);

        return [
            'today_visits' => $todayVisits,
            'today_returning' => $todayReturning,
            'redeemable' => $redeemable,
            'close_to_reward' => $closeToReward,
            'returning_month' => $returningMonth,
            'returning_spend' => $returningSpend,
            'happened' => trans_choice('loop.pulse_happened', $todayVisits),
            'happened_returning' => $todayReturning > 0
                ? trans_choice('loop.pulse_happened_returning', $todayReturning, ['count' => $todayReturning])
                : null,
            'happening' => trans_choice('loop.pulse_happening', $redeemable),
            'needs' => $needs,
            'next' => $next,
            'suggestion' => $suggestion,
            'prompts' => $prompts,
            'milestone' => $milestone,
            'reason_to_return' => trans_choice('loop.pulse_reason_to_return', $memberCount, ['count' => $memberCount]),
            'campaign_return_line' => $momentum['campaign_returners'] > 0
                ? trans_choice('loop.pulse_campaign_returners', $momentum['campaign_returners'], ['count' => $momentum['campaign_returners']])
                : null,
            'money_line' => $returningMonth > 0
                ? __('loop.pulse_money', [
                    'count' => $returningMonth,
                    'currency' => $business->currency,
                    'amount' => number_format($returningSpend, 0),
                ])
                : ($momentum['month_spend'] > 0
                    ? __('loop.pulse_loop_spend', [
                        'currency' => $business->currency,
                        'amount' => number_format($momentum['month_spend'], 0),
                    ])
                    : null),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}|null
     */
    private function needs(Business $business, ?Campaign $ending): ?array
    {
        if ($ending) {
            return [
                'title' => __('loop.pulse_needs_ending', [
                    'name' => $ending->displayName(),
                    'when' => $ending->ends_at->isoFormat('dddd'),
                ]),
                'body' => __('loop.pulse_needs_ending_body'),
                'url' => route('campaigns.show', $ending),
            ];
        }

        if ($business->rewards()->where('is_active', true)->doesntExist()) {
            return [
                'title' => __('loop.pulse_needs_offer'),
                'body' => __('loop.pulse_needs_offer_body'),
                'url' => route('rewards.create'),
            ];
        }

        if ($business->campaigns()->where('type', 'earn')->where('is_active', true)->doesntExist()) {
            return [
                'title' => __('loop.pulse_needs_earn'),
                'body' => __('loop.pulse_needs_earn_body'),
                'url' => route('campaigns.create'),
            ];
        }

        $banner = $business->subscriptionBanner();
        $access = app(LoopAccess::class);
        if ($access->isPaused($business)) {
            $demand = $access->momentum($business)['loop_back'];

            return [
                'title' => $demand > 0
                    ? trans_choice('loop.loop_paused_demand_title', $demand, ['count' => $demand])
                    : __('loop.loop_paused_title'),
                'body' => __('loop.loop_paused_safe'),
                'url' => route('billing.show'),
            ];
        }
        if (! empty($banner['text'])) {
            return [
                'title' => $banner['text'],
                'body' => __('loop.renew_now'),
                'url' => route('billing.show'),
            ];
        }

        return null;
    }

    /**
     * @param  array{title: string, body: string, url: string}|null  $needs
     * @return array{title: string, cta: string, url: string}
     */
    private function nextAction(Business $business, int $redeemable, int $close, ?array $needs): array
    {
        if ($needs && str_contains($needs['url'], 'billing')) {
            return [
                'title' => $needs['title'],
                'cta' => __('loop.reactivate_loop'),
                'url' => $needs['url'],
            ];
        }

        if ($needs && str_contains($needs['url'], 'offers/create')) {
            return [
                'title' => $needs['title'],
                'cta' => __('loop.create_next_offer'),
                'url' => $needs['url'],
            ];
        }

        if ($business->rewards()->doesntExist()) {
            return [
                'title' => __('loop.pulse_needs_offer'),
                'cta' => __('loop.create_next_offer'),
                'url' => route('rewards.create'),
            ];
        }

        if ($close > 0) {
            return [
                'title' => trans_choice('loop.pulse_close_cta', $close, ['count' => $close]),
                'cta' => __('loop.create_next_offer'),
                'url' => route('rewards.create'),
            ];
        }

        return [
            'title' => __('loop.pulse_open_till'),
            'cta' => __('loop.open_sale'),
            'url' => route('till.index'),
        ];
    }

    /**
     * @return array{title: string, body: string, cta: string, url: string}|null
     */
    private function suggestion(Business $business, int $close, int $returningMonth, int $redeemable): ?array
    {
        if ($close >= 5) {
            return [
                'title' => __('loop.pulse_suggest_close_title'),
                'body' => trans_choice('loop.pulse_suggest_close_body', $close, ['count' => $close]),
                'cta' => __('loop.create_next_offer'),
                'url' => route('rewards.create'),
            ];
        }

        if ($returningMonth >= 8) {
            return [
                'title' => __('loop.pulse_suggest_return_title'),
                'body' => trans_choice('loop.pulse_suggest_return_body', $returningMonth, ['count' => $returningMonth]),
                'cta' => __('loop.nice'),
                'url' => route('customers.index', ['sort' => 'visits']),
            ];
        }

        if ($redeemable > 0) {
            return [
                'title' => __('loop.pulse_suggest_ready_title'),
                'body' => trans_choice('loop.pulse_happening_full', $redeemable, ['count' => $redeemable]),
                'cta' => __('loop.open_sale'),
                'url' => route('till.index'),
            ];
        }

        return null;
    }

    /**
     * Swipeable owner home cards. Critical items also land in the notification bell.
     *
     * @param  array{title: string, body: string, cta: string, url: string}|null  $suggestion
     * @param  array<string, mixed>  $momentum
     * @return list<array{key: string, eyebrow: string, title: string, body: string, cta: string, url: string}>
     */
    private function prompts(Business $business, ?array $suggestion, int $returningMonth, float $returningSpend, array $momentum): array
    {
        $items = [];

        $banner = $business->subscriptionBanner();
        if (! empty($banner['text'])) {
            $items[] = [
                'key' => 'billing',
                'eyebrow' => __('loop.pulse_something_you_could'),
                'title' => $banner['text'],
                'body' => __('loop.renew_now'),
                'cta' => __('loop.renew_now'),
                'url' => route('billing.show'),
            ];
        }

        if (\App\Support\FeatureFlags::enabled('raffles')) {
            $upcoming = $business->raffles()
                ->where('is_active', true)
                ->whereIn('status', ['scheduled', 'live'])
                ->get()
                ->filter(function ($raffle) {
                    $next = $raffle->nextDrawDate();

                    return $next->lte(now()->addDay()->endOfDay()) && $raffle->remainingWinnerSlots() > 0;
                })
                ->sortBy(fn ($raffle) => $raffle->nextDrawDate()->timestamp)
                ->values();

            foreach ($upcoming as $raffle) {
                $next = $raffle->nextDrawDate();
                $today = $next->isSameDay(today());
                $items[] = [
                    'key' => 'raffle_'.$raffle->id,
                    'eyebrow' => __('loop.pulse_something_you_could'),
                    'title' => $today ? __('loop.pulse_raffle_today_title') : __('loop.pulse_raffle_soon_title'),
                    'body' => $today
                        ? __('loop.pulse_raffle_today_body', ['name' => $raffle->name, 'prize' => $raffle->prize_name])
                        : __('loop.pulse_raffle_soon_body', ['name' => $raffle->name, 'date' => $next->format('j M')]),
                    'cta' => $raffle->canDrawNow() ? __('loop.start_live_draw') : __('loop.view_raffle'),
                    'url' => $raffle->canDrawNow() ? route('raffles.live', $raffle) : route('raffles.show', $raffle),
                ];
            }
        }

        if ($suggestion) {
            $items[] = [
                'key' => 'suggestion',
                'eyebrow' => __('loop.pulse_something_you_could'),
                'title' => $suggestion['title'],
                'body' => $suggestion['body'],
                'cta' => $suggestion['cta'],
                'url' => $suggestion['url'],
            ];
        }

        if ($returningMonth > 0) {
            $items[] = [
                'key' => 'returning',
                'eyebrow' => __('loop.notifications'),
                'title' => __('loop.pulse_money_title'),
                'body' => __('loop.pulse_money', [
                    'count' => $returningMonth,
                    'currency' => $business->currency,
                    'amount' => number_format($returningSpend, 0),
                ]),
                'cta' => __('loop.view_customers'),
                'url' => route('customers.index', ['sort' => 'visits']),
            ];
        } elseif (($momentum['month_spend'] ?? 0) > 0 && $suggestion === null) {
            $items[] = [
                'key' => 'spend',
                'eyebrow' => __('loop.notifications'),
                'title' => __('loop.pulse_loop_spend_title'),
                'body' => __('loop.pulse_loop_spend', [
                    'currency' => $business->currency,
                    'amount' => number_format($momentum['month_spend'], 0),
                ]),
                'cta' => __('loop.view_all'),
                'url' => route('transactions.index'),
            ];
        }

        $seen = [];
        $unique = [];
        foreach ($items as $item) {
            $sig = $item['title'].'|'.$item['url'];
            if (isset($seen[$sig])) {
                continue;
            }
            $seen[$sig] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    /**
     * @return array{title: string, body: string}|null
     */
    private function milestone(int $members, int $newThisWeek): ?array
    {
        foreach ([1000, 500, 250, 100, 50, 10] as $mark) {
            if ($members >= $mark && $members - $newThisWeek < $mark) {
                return [
                    'title' => __('loop.pulse_milestone_title', ['count' => $mark]),
                    'body' => __('loop.pulse_milestone_body', ['count' => $mark]),
                ];
            }
        }

        return null;
    }
}
