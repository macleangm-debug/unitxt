<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\PointTransaction;
use App\Services\LoopAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', '');

        $memberships = $user
            ->memberships()
            ->with([
                'business.shops' => fn ($q) => $q->where('is_active', true)->orderBy('id'),
                'business.rewards' => fn ($q) => $q->where('is_active', true)->orderBy('points_cost'),
                'business.campaigns' => fn ($q) => $q->where('is_active', true)->latest(),
            ])
            ->latest()
            ->limit(200)
            ->get();

        if ($search !== '') {
            $q = strtolower($search);
            $memberships = $memberships->filter(function ($membership) use ($q) {
                $hay = strtolower(trim(implode(' ', array_filter([
                    $membership->business?->name,
                    $membership->nearestReadyReward()?->name,
                    $membership->nextReward()?->name,
                ]))));

                return str_contains($hay, $q);
            })->values();
        }

        $ready = $memberships
            ->flatMap(function ($membership) {
                return $membership->availableRewards()->map(fn ($reward) => [
                    'membership' => $membership,
                    'business' => $membership->business,
                    'reward' => $reward,
                ]);
            })
            ->values();

        $almost = $memberships
            ->map(function ($membership) {
                $next = $membership->nextReward();
                if (! $next) {
                    return null;
                }
                $progress = $membership->progressTo($next);
                if (($progress['needed'] ?? 0) <= 0) {
                    return null;
                }

                return [
                    'membership' => $membership,
                    'business' => $membership->business,
                    'reward' => $next,
                    'progress' => $progress,
                ];
            })
            ->filter()
            ->values();

        $offers = $memberships
            ->flatMap(function ($membership) {
                return ($membership->business?->campaigns ?? collect())->map(fn (Campaign $campaign) => [
                    'membership' => $membership,
                    'business' => $membership->business,
                    'campaign' => $campaign,
                ]);
            })
            ->values();

        $used = PointTransaction::query()
            ->where('type', PointTransaction::TYPE_REDEEM)
            ->whereIn('membership_id', $memberships->pluck('id'))
            ->with(['membership.business'])
            ->latest('id')
            ->limit(20)
            ->get();

        $showReady = $filter === '' || $filter === 'ready';
        $showAlmost = $filter === '' || $filter === 'almost';
        $showOffers = $filter === '' || $filter === 'offers';
        $showUsed = $filter === 'used';

        return view('memberships.index', [
            'memberships' => $memberships,
            'ready' => $showReady ? $ready : collect(),
            'almost' => $showAlmost ? $almost : collect(),
            'offers' => $showOffers ? $offers : collect(),
            'used' => $showUsed ? $used : collect(),
            'readyCount' => $ready->count(),
            'search' => $search,
            'filter' => $filter,
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
