<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\MembershipService;
use Illuminate\Http\RedirectResponse;
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
            ->get();

        return view('memberships.index', [
            'memberships' => $memberships,
        ]);
    }

    public function join(Request $request, Business $business, MembershipService $memberships): RedirectResponse
    {
        abort_unless($request->user()->isCustomer(), 403);
        abort_unless($business->is_active, 404);

        $memberships->join($business, $request->user());

        return redirect()
            ->route('dashboard')
            ->with('status', "You joined {$business->name}. Visit a shop to start earning points.");
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
