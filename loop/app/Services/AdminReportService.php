<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Business;
use App\Models\BusinessReferral;
use App\Models\Membership;
use App\Models\Plan;
use App\Models\User;
use App\Models\Visit;
use App\Support\FeatureFlags;
use App\Support\Plans;
use App\Support\Sectors;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    /**
     * Platform pulse — mixes till GMV (customer spend at shops) with Loop subscription state.
     * Until Mobile Money billing lands, subscription figures are booked MRR from active plan prices,
     * not collected cash.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->copy()->startOfMonth();
        $packages = $this->packagePerformance();

        return [
            // Till / loyalty economy (customer spend recorded at shops)
            'sales_today' => Visit::query()->whereDate('created_at', $today)->count(),
            'revenue_today' => (float) Visit::query()->whereDate('created_at', $today)->sum('amount_spent'),
            'sales_month' => Visit::query()->where('created_at', '>=', $monthStart)->count(),
            'revenue_month' => (float) Visit::query()->where('created_at', '>=', $monthStart)->sum('amount_spent'),
            'sales_all' => Visit::query()->count(),
            'revenue_all' => (float) Visit::query()->sum('amount_spent'),
            'avg_ticket_month' => $this->avgTicketSince($monthStart),
            'avg_ticket_all' => $this->avgTicketSince(null),

            // Customers & brands
            'unique_customers' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
            'unique_customers_with_membership' => (int) Membership::query()->selectRaw('COUNT(DISTINCT customer_id) as c')->value('c'),
            'active_businesses' => Business::query()->where('is_active', true)->count(),
            'businesses_total' => Business::query()->count(),
            'businesses_new_14d' => Business::query()->where('created_at', '>=', now()->subDays(14))->count(),
            'businesses_new_30d' => Business::query()->where('created_at', '>=', now()->subDays(30))->count(),

            // Loop subscriptions (package state — not yet Mobile Money cash)
            'trialing' => Business::query()->where('billing_status', 'trialing')->count(),
            'past_due' => Business::query()->where('billing_status', 'past_due')->count(),
            'suspended' => Business::query()->where('billing_status', 'suspended')->count(),
            'free_lane' => Business::query()->where('billing_status', 'free')->count(),
            'paid_active' => Business::query()
                ->where('billing_status', 'active')
                ->whereIn('plan_key', [Plans::STARTER, Plans::GROWTH, Plans::SCALE])
                ->count(),
            'estimated_mrr' => (float) $packages->sum('estimated_mrr'),
            'top_package' => $packages->sortByDesc('subscribers')->first(),
            'top_package_by_mrr' => $packages->sortByDesc('estimated_mrr')->first(),
            'trial_conversion_pct' => $this->trialConversionPct(),

            // Growth channels
            'affiliate_pending' => Affiliate::query()->where('status', 'pending')->count(),
            'affiliate_active' => Affiliate::query()->where('status', 'active')->count(),
            'referral_pending' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_PENDING)->count(),
            'referral_rewarded' => BusinessReferral::query()->where('status', BusinessReferral::STATUS_REWARDED)->count(),
            'referral_total' => BusinessReferral::query()->count(),

            // Product surface
            'features_on' => collect(FeatureFlags::settings())->filter()->count(),
            'features_off' => collect(FeatureFlags::settings())->reject()->count(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function packagePerformance(): Collection
    {
        $plans = Plan::query()->orderBy('sort_order')->get()->keyBy('key');
        $rows = Business::query()
            ->select(
                'plan_key',
                'billing_status',
                DB::raw('COUNT(*) as businesses')
            )
            ->groupBy('plan_key', 'billing_status')
            ->get();

        $byPlan = [];
        foreach ($plans as $key => $plan) {
            $byPlan[$key] = (object) [
                'plan_key' => $key,
                'name' => $plan->name,
                'price_monthly' => (int) $plan->price_monthly,
                'currency' => $plan->currency,
                'subscribers' => 0,
                'trialing' => 0,
                'other' => 0,
                'total' => 0,
                'estimated_mrr' => 0.0,
            ];
        }

        foreach ($rows as $row) {
            $key = (string) $row->plan_key;
            if (! isset($byPlan[$key])) {
                $byPlan[$key] = (object) [
                    'plan_key' => $key,
                    'name' => ucfirst($key),
                    'price_monthly' => 0,
                    'currency' => 'TZS',
                    'subscribers' => 0,
                    'trialing' => 0,
                    'other' => 0,
                    'total' => 0,
                    'estimated_mrr' => 0.0,
                ];
            }
            $count = (int) $row->businesses;
            $byPlan[$key]->total += $count;
            if ($row->billing_status === 'active' && Plans::isPaidPlan($key)) {
                $byPlan[$key]->subscribers += $count;
                $byPlan[$key]->estimated_mrr += $count * (float) $byPlan[$key]->price_monthly;
            } elseif ($row->billing_status === 'trialing') {
                $byPlan[$key]->trialing += $count;
            } else {
                $byPlan[$key]->other += $count;
            }
        }

        return collect(array_values($byPlan))->sortByDesc('total')->values();
    }

    /**
     * Businesses concentrated by sector (platform mix), not till GMV.
     *
     * @return Collection<int, object>
     */
    public function businessesBySector(): Collection
    {
        return Business::query()
            ->select(
                'sector',
                DB::raw('COUNT(*) as businesses'),
                DB::raw("SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_businesses"),
                DB::raw("SUM(CASE WHEN billing_status = 'active' THEN 1 ELSE 0 END) as paid_businesses")
            )
            ->groupBy('sector')
            ->orderByDesc('businesses')
            ->get()
            ->map(function ($row) {
                $row->sector_label = Sectors::label($row->sector);

                return $row;
            });
    }

    /**
     * @return Collection<int, object>
     */
    public function customersBySector(): Collection
    {
        return Membership::query()
            ->join('businesses', 'memberships.business_id', '=', 'businesses.id')
            ->select('businesses.sector', DB::raw('COUNT(DISTINCT memberships.customer_id) as unique_customers'))
            ->groupBy('businesses.sector')
            ->orderByDesc('unique_customers')
            ->get()
            ->map(function ($row) {
                $row->sector_label = Sectors::label($row->sector);

                return $row;
            });
    }

    /**
     * @return Collection<int, object>
     */
    public function customersByBusiness(int $limit = 50): Collection
    {
        $memberCounts = Membership::query()
            ->select('business_id', DB::raw('COUNT(DISTINCT customer_id) as unique_customers'))
            ->groupBy('business_id');

        $visitStats = Visit::query()
            ->select(
                'business_id',
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(amount_spent), 0) as revenue')
            )
            ->groupBy('business_id');

        return Business::query()
            ->leftJoinSub($memberCounts, 'mc', 'businesses.id', '=', 'mc.business_id')
            ->leftJoinSub($visitStats, 'vs', 'businesses.id', '=', 'vs.business_id')
            ->select(
                'businesses.id',
                'businesses.name',
                'businesses.sector',
                'businesses.city',
                'businesses.plan_key',
                'businesses.billing_status',
                DB::raw('COALESCE(mc.unique_customers, 0) as unique_customers'),
                DB::raw('COALESCE(vs.sales_count, 0) as sales_count'),
                DB::raw('COALESCE(vs.revenue, 0) as revenue')
            )
            ->orderByDesc('unique_customers')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->sector_label = Sectors::label($row->sector);

                return $row;
            });
    }

    /**
     * Till GMV by sector (customer spend at shops — not Loop subscription revenue).
     *
     * @return Collection<int, object>
     */
    public function salesBySector(): Collection
    {
        $bizCounts = Business::query()
            ->select('sector', DB::raw('COUNT(*) as businesses'))
            ->groupBy('sector');

        return Visit::query()
            ->join('businesses', 'visits.business_id', '=', 'businesses.id')
            ->leftJoinSub($bizCounts, 'bc', 'businesses.sector', '=', 'bc.sector')
            ->select(
                'businesses.sector',
                DB::raw('COUNT(visits.id) as sales_count'),
                DB::raw('COALESCE(SUM(visits.amount_spent), 0) as revenue'),
                DB::raw('COUNT(DISTINCT visits.customer_id) as unique_customers'),
                DB::raw('MAX(bc.businesses) as businesses')
            )
            ->groupBy('businesses.sector')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $row->sector_label = Sectors::label($row->sector);
                $row->businesses = (int) ($row->businesses ?? 0);

                return $row;
            });
    }

    /**
     * @return Collection<int, object>
     */
    public function dailySales(int $days = 14): Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = Visit::query()
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(amount_spent), 0) as revenue')
            )
            ->where('created_at', '>=', $start)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $out = collect();
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $row = $rows->get($day);
            $out->push((object) [
                'day' => $day,
                'sales_count' => (int) ($row->sales_count ?? 0),
                'revenue' => (float) ($row->revenue ?? 0),
            ]);
        }

        return $out;
    }

    /**
     * New businesses signing up per day (platform growth).
     *
     * @return Collection<int, object>
     */
    public function dailySignups(int $days = 14): Collection
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $rows = Business::query()
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as signups')
            )
            ->where('created_at', '>=', $start)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $out = collect();
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $out->push((object) [
                'day' => $day,
                'signups' => (int) ($rows->get($day)->signups ?? 0),
            ]);
        }

        return $out;
    }

    private function avgTicketSince(mixed $since): float
    {
        $q = Visit::query();
        if ($since !== null) {
            $q->where('created_at', '>=', $since);
        }
        $count = (clone $q)->count();
        if ($count === 0) {
            return 0.0;
        }

        return round((float) $q->sum('amount_spent') / $count, 0);
    }

    private function trialConversionPct(): int
    {
        $paid = Business::query()
            ->where('billing_status', 'active')
            ->whereIn('plan_key', [Plans::STARTER, Plans::GROWTH, Plans::SCALE])
            ->count();
        $trialing = Business::query()->where('billing_status', 'trialing')->count();
        $denom = $paid + $trialing;
        if ($denom === 0) {
            return 0;
        }

        return (int) round(($paid / $denom) * 100);
    }

    /**
     * @return Collection<int, object>
     */
    public function affiliatesExport(): Collection
    {
        return Affiliate::query()
            ->latest()
            ->get()
            ->map(fn (Affiliate $a) => (object) [
                'name' => $a->name,
                'full_phone' => $a->full_phone,
                'status' => $a->status,
                'promo_code' => $a->promo_code,
                'created_at' => $a->created_at,
            ]);
    }

    /**
     * @return Collection<int, object>
     */
    public function affiliateReferralsExport(): Collection
    {
        return \App\Models\AffiliateReferral::query()
            ->with(['affiliate', 'business'])
            ->latest()
            ->limit(2000)
            ->get()
            ->map(fn ($r) => (object) [
                'affiliate_name' => $r->affiliate?->name,
                'business_name' => $r->business?->name,
                'status' => $r->status,
                'commission_amount' => $r->commission_amount,
                'created_at' => $r->created_at,
            ]);
    }

    /**
     * @return Collection<int, object>
     */
    public function businessReferralsExport(): Collection
    {
        return BusinessReferral::query()
            ->with(['referrer', 'referred'])
            ->latest()
            ->limit(2000)
            ->get()
            ->map(fn ($r) => (object) [
                'referrer_name' => $r->referrer?->name,
                'referred_name' => $r->referred?->name,
                'code_used' => $r->code_used,
                'status' => $r->status,
                'created_at' => $r->created_at,
            ]);
    }

    /**
     * @return Collection<int, object>
     */
    public function paymentsExport(): Collection
    {
        return \App\Models\PaymentIntent::query()
            ->latest()
            ->limit(2000)
            ->get()
            ->map(fn ($p) => (object) [
                'reference' => $p->provider_ref ?? $p->uuid,
                'purpose' => $p->purpose,
                'amount' => $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'phone' => $p->phone,
                'created_at' => $p->created_at,
            ]);
    }
}
