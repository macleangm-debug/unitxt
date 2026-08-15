<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $sort = $request->query('sort', 'spend');
        $q = trim((string) $request->query('q', ''));
        $tab = $request->query('tab', 'all');

        $stats = Visit::query()
            ->select('customer_id')
            ->selectRaw('COUNT(*) as visits_count')
            ->selectRaw('COALESCE(SUM(amount_spent), 0) as total_spend')
            ->selectRaw('COALESCE(SUM(points_earned), 0) as points_earned')
            ->where('business_id', $business->id)
            ->groupBy('customer_id');

        $rows = Membership::query()
            ->where('memberships.business_id', $business->id)
            ->selectRaw('memberships.customer_id, SUM(memberships.points_balance) as points_balance, SUM(memberships.lifetime_points) as lifetime_points, MIN(memberships.joined_at) as first_joined_at')
            ->groupBy('memberships.customer_id')
            ->leftJoinSub($stats, 'visit_stats', function ($join) {
                $join->on('visit_stats.customer_id', '=', 'memberships.customer_id');
            })
            ->addSelect([
                DB::raw('COALESCE(visit_stats.visits_count, 0) as visits_count'),
                DB::raw('COALESCE(visit_stats.total_spend, 0) as total_spend'),
                DB::raw('COALESCE(visit_stats.points_earned, 0) as points_earned'),
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereIn('memberships.customer_id', function ($sub) use ($q) {
                    $sub->select('id')->from('users')
                        ->where('first_name', 'like', '%'.$q.'%')
                        ->orWhere('last_name', 'like', '%'.$q.'%')
                        ->orWhere('phone', 'like', '%'.$q.'%');
                });
            })
            ->when($tab === 'new', function ($query) {
                $query->whereIn('memberships.customer_id', function ($sub) {
                    $sub->select('customer_id')
                        ->from('memberships as m2')
                        ->whereColumn('m2.business_id', 'memberships.business_id')
                        ->groupBy('customer_id')
                        ->havingRaw('MIN(m2.joined_at) >= ?', [now()->subDays(30)]);
                });
            })
            ->when($tab === 'ready', function ($query) {
                $query->whereIn('memberships.customer_id', function ($sub) {
                    $sub->select('customer_id')
                        ->from('memberships as m3')
                        ->whereColumn('m3.business_id', 'memberships.business_id')
                        ->groupBy('customer_id')
                        ->havingRaw('SUM(m3.points_balance) >= 100');
                });
            })
            ->when($sort === 'visits', fn ($query) => $query->orderByDesc('visits_count')->orderByDesc('total_spend'))
            ->when($sort === 'points', fn ($query) => $query->orderByDesc('lifetime_points'))
            ->when($sort === 'spend', fn ($query) => $query->orderByDesc('total_spend')->orderByDesc('visits_count'))
            ->when($sort === 'recent', fn ($query) => $query->orderByDesc('first_joined_at'))
            ->paginate(30)
            ->withQueryString();

        $users = User::query()
            ->whereIn('id', $rows->getCollection()->pluck('customer_id'))
            ->get()
            ->keyBy('id');

        $rows->setCollection(
            $rows->getCollection()->map(function ($row) use ($users) {
                $user = $users->get($row->customer_id);
                if (! $user) {
                    return null;
                }
                $user->points_balance = (int) $row->points_balance;
                $user->lifetime_points = (int) $row->lifetime_points;
                $user->first_joined_at = $row->first_joined_at ? \Illuminate\Support\Carbon::parse($row->first_joined_at) : null;
                $user->visits_count = (int) $row->visits_count;
                $user->total_spend = (float) $row->total_spend;

                return $user;
            })->filter()->values()
        );

        $memberCount = Membership::query()
            ->where('business_id', $business->id)
            ->distinct()
            ->count('customer_id');

        $newThisMonth = Membership::query()
            ->where('business_id', $business->id)
            ->where('joined_at', '>=', now()->startOfMonth())
            ->distinct()
            ->count('customer_id');

        $readyCount = Membership::query()
            ->where('business_id', $business->id)
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('SUM(points_balance) >= 100')
            ->get()
            ->count();

        $topSpenders = collect($rows->items())->take(3);

        return view('customers.index', [
            'business' => $business,
            'customers' => $rows,
            'memberCount' => $memberCount,
            'sort' => $sort,
            'tab' => $tab,
            'q' => $q,
            'topSpenders' => $topSpenders,
            'summary' => [
                'members' => $memberCount,
                'new_month' => $newThisMonth,
                'ready' => $readyCount,
            ],
        ]);
    }

    public function show(Request $request, User $customer): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $memberships = Membership::query()
            ->with('shop')
            ->where('business_id', $business->id)
            ->where('customer_id', $customer->id)
            ->get();

        abort_unless($memberships->isNotEmpty(), 404);

        $visits = $business->visits()
            ->with(['shop', 'campaign', 'reward'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->take(20)
            ->get();

        $raffleWins = \App\Models\RaffleWinner::query()
            ->with('raffle')
            ->where('customer_id', $customer->id)
            ->whereHas('raffle', fn ($q) => $q->where('business_id', $business->id))
            ->latest('drawn_at')
            ->get();

        return view('customers.show', [
            'business' => $business,
            'customer' => $customer,
            'memberships' => $memberships,
            'visits' => $visits,
            'raffleWins' => $raffleWins,
            'points' => (int) $memberships->sum('points_balance'),
            'lifetime' => (int) $memberships->sum('lifetime_points'),
            'visitCount' => $visits->count() > 0
                ? $business->visits()->where('customer_id', $customer->id)->count()
                : 0,
            'totalSpend' => (float) $business->visits()->where('customer_id', $customer->id)->sum('amount_spent'),
        ]);
    }
}
