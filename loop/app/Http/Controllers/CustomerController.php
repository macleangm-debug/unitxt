<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $rows = Membership::query()
            ->where('business_id', $business->id)
            ->selectRaw('customer_id, SUM(points_balance) as points_balance, SUM(lifetime_points) as lifetime_points, MIN(joined_at) as first_joined_at')
            ->groupBy('customer_id')
            ->orderByDesc('lifetime_points')
            ->paginate(30);

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

                return $user;
            })->filter()->values()
        );

        return view('customers.index', [
            'business' => $business,
            'customers' => $rows,
            'memberCount' => Membership::query()
                ->where('business_id', $business->id)
                ->distinct()
                ->count('customer_id'),
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

        return view('customers.show', [
            'business' => $business,
            'customer' => $customer,
            'memberships' => $memberships,
            'visits' => $visits,
            'points' => (int) $memberships->sum('points_balance'),
            'lifetime' => (int) $memberships->sum('lifetime_points'),
        ]);
    }
}
