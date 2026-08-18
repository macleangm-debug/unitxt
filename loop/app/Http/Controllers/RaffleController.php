<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Services\RaffleService;
use App\Support\Confirm;
use App\Support\GrowthSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaffleController extends Controller
{
    public function index(Request $request): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $memberCount = $business->uniqueMemberCount();
        $min = GrowthSettings::raffleMinMembers();
        $planAllows = app(\App\Services\PlanLimitService::class)->rafflesEnabled($business);

        return view('raffles.index', [
            'business' => $business,
            'raffles' => $business->raffles()->withCount('winners')->latest()->get(),
            'unlocked' => $planAllows && $memberCount >= $min,
            'planLocked' => ! $planAllows,
            'memberCount' => $memberCount,
            'minMembers' => $min,
            'reminders' => $business->raffles()
                ->where('status', 'scheduled')
                ->whereDate('draw_at', '<=', now()->addDays(GrowthSettings::settings()['raffle_remind_days_before']))
                ->orderBy('draw_at')
                ->get(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $memberCount = $business->uniqueMemberCount();
        abort_unless(app(\App\Services\PlanLimitService::class)->rafflesEnabled($business), 403);
        if ($memberCount < GrowthSettings::raffleMinMembers()) {
            return redirect()->route('raffles.index')
                ->withErrors(['raffle' => __('loop.raffle_locked_body', [
                    'need' => GrowthSettings::raffleMinMembers(),
                    'have' => $memberCount,
                ])]);
        }

        $maxWinners = GrowthSettings::maxWinnersForMembers($memberCount);

        return view('raffles.create', [
            'business' => $business,
            'memberCount' => $memberCount,
            'maxWinners' => $maxWinners,
            'maxWinnersPercent' => GrowthSettings::raffleMaxWinnersPercent(),
            'defaultClaimDays' => GrowthSettings::settings()['raffle_default_claim_days'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $memberCount = $business->uniqueMemberCount();
        abort_unless(app(\App\Services\PlanLimitService::class)->rafflesEnabled($business), 403);
        abort_unless($memberCount >= GrowthSettings::raffleMinMembers(), 403);

        $maxWinners = GrowthSettings::maxWinnersForMembers($memberCount);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'prize_name' => ['required', 'string', 'max:120'],
            'prize_type' => ['required', 'in:free_item,percent_off,fixed_off,custom'],
            'prize_value' => ['nullable', 'numeric', 'min:0'],
            'winners_count' => ['required', 'integer', 'min:1', 'max:'.$maxWinners],
            'frequency' => ['required', 'in:once,weekly,monthly,yearly'],
            'draw_at' => ['required', 'date', 'after_or_equal:today'],
            'claim_days' => ['required', 'integer', 'min:1', 'max:30'],
        ], [
            'winners_count.max' => __('loop.raffle_winners_max_error', [
                'max' => $maxWinners,
                'pct' => GrowthSettings::raffleMaxWinnersPercent(),
                'members' => $memberCount,
            ]),
        ]);

        $raffle = $business->raffles()->create([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => 'scheduled',
            'is_active' => true,
        ]);

        return redirect()->route('raffles.show', $raffle)->with('confirm', Confirm::make(
            __('loop.raffle_created_title'),
            __('loop.raffle_created_body', ['name' => $raffle->name]),
            __('loop.view_raffle'),
            route('raffles.show', $raffle),
        ));
    }

    public function show(Request $request, Raffle $raffle): View
    {
        $this->authorizeRaffle($request, $raffle);

        return view('raffles.show', [
            'business' => $raffle->business,
            'raffle' => $raffle->load(['winners.customer']),
            'eligibleCount' => app(RaffleService::class)->eligibleCustomers($raffle->business)->count(),
        ]);
    }

    public function live(Request $request, Raffle $raffle): View
    {
        $this->authorizeRaffle($request, $raffle);

        return view('raffles.live', [
            'business' => $raffle->business,
            'raffle' => $raffle->load(['winners.customer']),
            'remaining' => $raffle->remainingWinnerSlots(),
        ]);
    }

    public function draw(Request $request, Raffle $raffle, RaffleService $raffles): RedirectResponse
    {
        $this->authorizeRaffle($request, $raffle);
        $winner = $raffles->drawNext($raffle, $request->user());

        return redirect()->route('raffles.live', $raffle)->with([
            'drawn_winner_id' => $winner->id,
            'confirm' => Confirm::make(
                __('loop.raffle_winner_title'),
                __('loop.raffle_winner_body', [
                    'name' => $winner->customer->first_name.' '.$winner->displayLastBlurred(),
                    'prize' => $raffle->prize_name,
                ]),
                $raffle->remainingWinnerSlots() > 0 ? __('loop.draw_next') : __('loop.view_raffle'),
                $raffle->remainingWinnerSlots() > 0
                    ? route('raffles.live', $raffle)
                    : route('raffles.show', $raffle),
            ),
        ]);
    }

    public function contact(Request $request, Raffle $raffle, \App\Models\RaffleWinner $winner, RaffleService $raffles): RedirectResponse
    {
        $this->authorizeRaffle($request, $raffle);
        abort_unless($winner->raffle_id === $raffle->id, 404);
        $raffles->markContacted($winner);

        return back()->with('confirm', Confirm::make(
            __('loop.raffle_contacted_title'),
            __('loop.raffle_contacted_body', ['name' => $winner->customer->name, 'phone' => $winner->customer->full_phone]),
            __('loop.done'),
            route('raffles.show', $raffle),
            false,
        ));
    }

    public function claim(Request $request, Raffle $raffle, \App\Models\RaffleWinner $winner, RaffleService $raffles): RedirectResponse
    {
        $this->authorizeRaffle($request, $raffle);
        abort_unless($winner->raffle_id === $raffle->id, 404);
        $raffles->markClaimed($winner);

        return back()->with('confirm', Confirm::make(
            __('loop.raffle_claimed_title'),
            __('loop.raffle_claimed_body', ['name' => $winner->customer->name]),
            __('loop.done'),
            route('raffles.show', $raffle),
            false,
        ));
    }

    private function authorizeRaffle(Request $request, Raffle $raffle): void
    {
        abort_unless($request->user()->ownedBusiness?->id === $raffle->business_id, 403);
    }
}
