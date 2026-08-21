<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\LoopAccess;
use Illuminate\Http\RedirectResponse;
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
                'business.rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost'),
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
        $access = app(LoopAccess::class);
        $paused = $access->isPaused($business);

        return view('memberships.show', [
            'business' => $business,
            'membership' => $membership,
            'transactions' => $membership->groupedActivity(20),
            'visits' => $membership->visits()->with(['shop', 'campaign'])->latest()->take(10)->get(),
            'rewards' => $business->rewards()->where('is_active', true)->orderBy('points_cost')->get(),
            'paused' => $paused,
            'wantedBack' => $paused && $access->wantsLoopBack($business, $request->user()->id),
            'raffleWins' => \App\Models\RaffleWinner::query()
                ->with('raffle')
                ->where('customer_id', $request->user()->id)
                ->whereHas('raffle', fn ($q) => $q->where('business_id', $business->id))
                ->latest('drawn_at')
                ->get(),
        ]);
    }

    public function wantBack(Request $request, Business $business): RedirectResponse
    {
        $membership = $business->memberships()
            ->where('customer_id', $request->user()->id)
            ->firstOrFail();

        $access = app(LoopAccess::class);
        abort_unless($access->isPaused($business), 404);

        $created = ! $access->wantsLoopBack($business, $request->user()->id);
        $access->requestLoopBack($business, $membership);

        if ($created) {
            $owner = $business->owner;
            if ($owner) {
                app(\App\Services\DailyNotificationService::class)->notifyLoopBack($owner, $business, $membership);
            }
        }

        return back()->with('status', __('loop.want_loop_back_thanks', ['name' => $business->name]));
    }
}
