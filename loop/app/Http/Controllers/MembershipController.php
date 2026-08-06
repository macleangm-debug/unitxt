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
            ->with('business')
            ->latest()
            ->get()
            ->groupBy(fn ($m) => $m->business->sector);

        return view('memberships.index', [
            'grouped' => $memberships,
        ]);
    }

    public function show(Request $request, Business $business): View
    {
        $membership = $business->memberships()
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        return view('memberships.show', [
            'business' => $business,
            'membership' => $membership,
            'transactions' => $membership->pointTransactions()->latest()->take(20)->get(),
            'visits' => $membership->visits()->with(['shop', 'campaign'])->latest()->take(10)->get(),
            'rewards' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->get(),
        ]);
    }
}
