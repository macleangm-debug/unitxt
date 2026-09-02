<?php

namespace App\Services;

use App\Models\Business;
use App\Models\LoopBackRequest;
use App\Models\Membership;
use App\Support\BillingSettings;
use App\Support\Plans;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class LoopAccess
{
    public const PHASE_ACTIVE = 'active';

    public const PHASE_TRIAL = 'trial';

    public const PHASE_GRACE = 'grace';

    public const PHASE_PAUSED = 'paused';

    public const CLOSE_POINTS = 20;

    public function coverageEndsAt(Business $business): ?Carbon
    {
        if (Plans::isPaidPlan($business->plan_key) && $business->plan_renews_at) {
            return $business->plan_renews_at;
        }

        return $business->trial_ends_at;
    }

    public function phase(Business $business): string
    {
        if ($business->billing_status === 'suspended') {
            return self::PHASE_PAUSED;
        }

        $end = $this->coverageEndsAt($business);

        if (Plans::isPaidPlan($business->plan_key) && $business->billing_status === 'active' && ! $business->plan_renews_at) {
            return self::PHASE_ACTIVE;
        }

        if (! $end) {
            return $business->billing_status === 'trialing' ? self::PHASE_TRIAL : self::PHASE_ACTIVE;
        }

        $daysPast = (int) $end->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);

        if ($daysPast <= 0) {
            if (Plans::isPaidPlan($business->plan_key) && $business->billing_status !== 'trialing') {
                return self::PHASE_ACTIVE;
            }

            return self::PHASE_TRIAL;
        }

        $graceDays = BillingSettings::graceDays();
        if ($graceDays > 0 && $daysPast <= $graceDays) {
            return self::PHASE_GRACE;
        }

        return self::PHASE_PAUSED;
    }

    public function isPaused(Business $business): bool
    {
        return $this->phase($business) === self::PHASE_PAUSED;
    }

    public function isGrace(Business $business): bool
    {
        return $this->phase($business) === self::PHASE_GRACE;
    }

    public function isPromoted(Business $business): bool
    {
        return $business->is_active && ! $this->isPaused($business);
    }

    public function daysUntilDue(Business $business): ?int
    {
        $end = $this->coverageEndsAt($business);
        if (! $end) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);
    }

    public function graceDaysLeft(Business $business): int
    {
        if (! $this->isGrace($business)) {
            return 0;
        }

        $end = $this->coverageEndsAt($business);
        if (! $end) {
            return 0;
        }

        $daysPast = (int) $end->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);
        $graceDays = BillingSettings::graceDays();

        return max(0, $graceDays + 1 - $daysPast);
    }

    public function sync(Business $business): Business
    {
        $phase = $this->phase($business);
        $updates = [];

        if ($phase === self::PHASE_PAUSED) {
            if (! in_array($business->billing_status, ['paused', 'suspended'], true)) {
                $updates['billing_status'] = 'paused';
            }
            if (! $business->paused_at) {
                $updates['paused_at'] = now();
            }
        } elseif ($phase === self::PHASE_GRACE) {
            if ($business->billing_status !== 'past_due') {
                $updates['billing_status'] = 'past_due';
            }
            if (! $business->grace_started_at) {
                $updates['grace_started_at'] = now();
            }
            if ($business->paused_at) {
                $updates['paused_at'] = null;
            }
        } elseif ($phase === self::PHASE_TRIAL) {
            if ($business->billing_status !== 'trialing') {
                $updates['billing_status'] = 'trialing';
            }
            if ($business->paused_at) {
                $updates['paused_at'] = null;
            }
            if ($business->grace_started_at) {
                $updates['grace_started_at'] = null;
            }
        } else {
            if ($business->billing_status !== 'active' && Plans::isPaidPlan($business->plan_key)) {
                $updates['billing_status'] = 'active';
            }
            if ($business->paused_at) {
                $updates['paused_at'] = null;
            }
            if ($business->grace_started_at) {
                $updates['grace_started_at'] = null;
            }
        }

        if ($updates !== []) {
            $business->update($updates);
        }

        return $business->fresh();
    }

    public function activate(Business $business, string $planKey, int $months, int $monthlyPrice): void
    {
        $months = BillingSettings::normalizeMonths($months);
        $payload = [
            'plan_key' => $planKey,
            'billing_status' => 'active',
            'trial_ends_at' => null,
            'plan_interval_months' => $months,
            'plan_renews_at' => now()->addMonths($months),
            'paused_at' => null,
            'grace_started_at' => null,
        ];

        if ($months >= 12 && $monthlyPrice > 0) {
            $payload['price_locked_until'] = now()->addMonths(12);
            $payload['price_locked_monthly'] = $monthlyPrice;
            $payload['price_locked_plan_key'] = $planKey;
        }

        $business->update($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function banner(Business $business): ?array
    {
        $phase = $this->phase($business);

        if ($phase === self::PHASE_PAUSED) {
            return [
                'tone' => 'coral',
                'text' => __('loop.loop_paused_banner'),
            ];
        }

        if ($phase === self::PHASE_GRACE) {
            $left = $this->graceDaysLeft($business);

            return [
                'tone' => 'coral',
                'text' => trans_choice('loop.loop_grace_banner', $left, ['days' => $left]),
            ];
        }

        $days = $this->daysUntilDue($business);
        if ($days !== null && $days >= 0 && $days <= 14) {
            if ($phase === self::PHASE_TRIAL) {
                return [
                    'tone' => $days <= 2 ? 'coral' : 'amber',
                    'text' => __('loop.sub_trial_ending', ['days' => $days]),
                ];
            }

            return [
                'tone' => $days <= 3 ? 'coral' : 'amber',
                'text' => __('loop.sub_renews_soon', [
                    'days' => $days,
                    'plan' => $business->plan?->name ?? $business->plan_key,
                ]),
            ];
        }

        return null;
    }

    /**
     * @return array<string, int|float>
     */
    public function momentum(Business $business): array
    {
        $memberships = $business->memberships()->with(['business.rewards' => fn ($q) => $q->where('is_active', true)])->get();
        $close = $memberships->filter(function (Membership $m) {
            $next = $m->nextReward();
            if (! $next) {
                return false;
            }
            $needed = $m->progressTo($next)['needed'] ?? 0;

            return $needed > 0 && $needed <= self::CLOSE_POINTS;
        })->count();
        $ready = $memberships->filter(fn (Membership $m) => $m->availableRewards()->isNotEmpty())->count();

        $monthStart = now()->startOfMonth();
        $monthVisits = $business->visits()->where('created_at', '>=', $monthStart);
        $campaignReturners = (int) (clone $monthVisits)
            ->whereNotNull('campaign_id')
            ->distinct('customer_id')
            ->count('customer_id');
        $rewardsUnlocked = (int) (clone $monthVisits)
            ->where('points_redeemed', '>', 0)
            ->count();
        $monthSpend = (float) (clone $monthVisits)->sum('amount_spent');
        return [
            'members' => $business->uniqueMemberCount(),
            'close_to_reward' => $close,
            'rewards_ready' => $ready,
            'active_campaigns' => $business->campaigns()->active()->count(),
            'active_offers' => $business->rewards()->where('is_active', true)->count(),
            'active_raffles' => $business->raffles()->whereIn('status', ['scheduled', 'drawn'])->count(),
            'month_visits' => (int) (clone $monthVisits)->count(),
            'month_spend' => $monthSpend,
            'campaign_returners' => $campaignReturners,
            'rewards_unlocked_month' => $rewardsUnlocked,
            'loop_back' => $business->loopBackRequests()->count(),
            'loop_back_close' => $business->loopBackRequests()->where('close_to_reward', true)->count(),
            'loop_back_ready' => $business->loopBackRequests()->where('reward_ready', true)->count(),
            'loop_back_with_points' => $business->loopBackRequests()->where('points_snapshot', '>', 0)->count(),
        ];
    }

    public function constrainPromoted(Builder $query): Builder
    {
        $graceDays = BillingSettings::graceDays();
        $cutoff = now()->startOfDay()->subDays($graceDays);

        return $query
            ->where('is_active', true)
            ->whereNotIn('billing_status', ['paused', 'suspended'])
            ->where(function (Builder $outer) use ($cutoff) {
                $outer->where(function (Builder $paid) use ($cutoff) {
                    $paid->whereIn('plan_key', [Plans::STARTER, Plans::GROWTH, Plans::SCALE])
                        ->where(function (Builder $cover) use ($cutoff) {
                            $cover->whereNull('plan_renews_at')
                                ->orWhere('plan_renews_at', '>=', $cutoff);
                        });
                })->orWhere(function (Builder $trial) use ($cutoff) {
                    $trial->where(function (Builder $key) {
                        $key->whereNull('plan_key')
                            ->orWhereNotIn('plan_key', [Plans::STARTER, Plans::GROWTH, Plans::SCALE]);
                    })->where(function (Builder $cover) use ($cutoff) {
                        $cover->whereNull('trial_ends_at')
                            ->orWhere('trial_ends_at', '>=', $cutoff);
                    });
                });
            });
    }

    public function requestLoopBack(Business $business, Membership $membership): LoopBackRequest
    {
        $next = $membership->nextReward();
        $needed = $next ? ($membership->progressTo($next)['needed'] ?? 0) : 0;

        return LoopBackRequest::query()->firstOrCreate(
            [
                'business_id' => $business->id,
                'customer_id' => $membership->customer_id,
            ],
            [
                'points_snapshot' => (int) $membership->points_balance,
                'close_to_reward' => $needed > 0 && $needed <= self::CLOSE_POINTS,
                'reward_ready' => $membership->availableRewards()->isNotEmpty(),
            ],
        );
    }

    public function wantsLoopBack(Business $business, int $customerId): bool
    {
        return LoopBackRequest::query()
            ->where('business_id', $business->id)
            ->where('customer_id', $customerId)
            ->exists();
    }
}
