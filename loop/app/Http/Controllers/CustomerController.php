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
        $gender = $request->query('gender');
        $gender = in_array($gender, ['male', 'female'], true) ? $gender : null;

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
            ->when($gender, function ($query) use ($gender) {
                $query->whereExists(function ($sub) use ($gender) {
                    $sub->from('users')
                        ->whereColumn('users.id', 'memberships.customer_id')
                        ->where('users.gender', $gender);
                });
            })
            ->addSelect([
                DB::raw('COALESCE(visit_stats.visits_count, 0) as visits_count'),
                DB::raw('COALESCE(visit_stats.total_spend, 0) as total_spend'),
                DB::raw('COALESCE(visit_stats.points_earned, 0) as points_earned'),
            ])
            ->when($sort === 'visits', fn ($q) => $q->orderByDesc('visits_count')->orderByDesc('total_spend'))
            ->when($sort === 'points', fn ($q) => $q->orderByDesc('lifetime_points'))
            ->when($sort === 'spend', fn ($q) => $q->orderByDesc('total_spend')->orderByDesc('visits_count'))
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

        $topSpenders = collect($rows->items())->take(3);

        return view('customers.index', [
            'business' => $business,
            'customers' => $rows,
            'memberCount' => Membership::query()
                ->where('business_id', $business->id)
                ->distinct()
                ->count('customer_id'),
            'sort' => $sort,
            'gender' => $gender,
            'topSpenders' => $topSpenders,
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
