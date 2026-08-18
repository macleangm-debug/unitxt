<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function index(Request $request): View
    {
        $memberships = $request->user()
            ->memberships()
            ->with([
                'business.shops' => fn ($q) => $q->where('is_active', true)->orderBy('id'),
            ])
            ->latest()
            ->get()
            ->groupBy(fn ($m) => $m->business->sector);

        return view('memberships.index', [
            'grouped' => $memberships,
        ]);
    }

    public function show(Request $request, Business $business): View
    {
        $business->load([
            'shops' => fn ($q) => $q->where('is_active', true)->orderBy('id'),
        ]);

        $membership = $business->memberships()
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        return view('memberships.show', [
            'business' => $business,
            'membership' => $membership,
            'transactions' => $membership->groupedActivity(20),
            'visits' => $membership->visits()->with(['shop', 'campaign'])->latest()->take(10)->get(),
            'rewards' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->get(),
            'raffleWins' => \App\Models\RaffleWinner::query()
                ->with('raffle')
                ->where('customer_id', $request->user()->id)
                ->whereHas('raffle', fn ($q) => $q->where('business_id', $business->id))
                ->latest('drawn_at')
                ->get(),
        ]);
    }
}
