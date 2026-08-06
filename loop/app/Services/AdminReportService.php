<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Membership;
use App\Models\User;
use App\Models\Visit;
use App\Support\Sectors;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->copy()->startOfMonth();

        return [
            'sales_today' => Visit::query()->whereDate('created_at', $today)->count(),
            'revenue_today' => (float) Visit::query()->whereDate('created_at', $today)->sum('amount_spent'),
            'sales_month' => Visit::query()->where('created_at', '>=', $monthStart)->count(),
            'revenue_month' => (float) Visit::query()->where('created_at', '>=', $monthStart)->sum('amount_spent'),
            'sales_all' => Visit::query()->count(),
            'revenue_all' => (float) Visit::query()->sum('amount_spent'),
            'unique_customers' => User::query()->where('role', User::ROLE_CUSTOMER)->count(),
            'unique_customers_with_membership' => (int) Membership::query()->selectRaw('COUNT(DISTINCT customer_id) as c')->value('c'),
            'active_businesses' => Business::query()->where('is_active', true)->count(),
            'trialing' => Business::query()->where('billing_status', 'trialing')->count(),
            'past_due' => Business::query()->where('billing_status', 'past_due')->count(),
            'paid_active' => Business::query()
                ->where('billing_status', 'active')
                ->whereIn('plan_key', ['starter', 'growth', 'scale'])
                ->count(),
        ];
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
     * @return Collection<int, object>
     */
    public function salesBySector(): Collection
    {
        return Visit::query()
            ->join('businesses', 'visits.business_id', '=', 'businesses.id')
            ->select(
                'businesses.sector',
                DB::raw('COUNT(visits.id) as sales_count'),
                DB::raw('COALESCE(SUM(visits.amount_spent), 0) as revenue'),
                DB::raw('COUNT(DISTINCT visits.customer_id) as unique_customers')
            )
            ->groupBy('businesses.sector')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $row->sector_label = Sectors::label($row->sector);

                return $row;
            });
    }

    /**
     * @return Collection<int, object>
     */
    public function dailySales(int $days = 14): Collection
    {
        return Visit::query()
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('COALESCE(SUM(amount_spent), 0) as revenue')
            )
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->get();
    }
}
