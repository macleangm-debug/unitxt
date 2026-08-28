<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use App\Services\RaffleService;
use App\Support\Confirm;
use App\Support\FeatureFlags;
use App\Support\GrowthSettings;
use Illuminate\Http\JsonResponse;
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

        $raffles = $business->raffles()->withCount(['winners as drawn_count'])->latest()->get();
        $remindDays = (int) GrowthSettings::settings()['raffle_remind_days_before'];

        return view('raffles.index', [
            'business' => $business,
            'raffles' => $raffles,
            'unlocked' => $planAllows && $memberCount >= $min,
            'planLocked' => ! $planAllows,
            'platformOff' => ! FeatureFlags::enabled('raffles'),
            'memberCount' => $memberCount,
            'eligibleCount' => $memberCount,
            'minMembers' => $min,
            'reminders' => $business->raffles()
                ->where('is_active', true)
                ->whereIn('status', ['scheduled', 'live'])
                ->get()
                ->filter(fn (Raffle $raffle) => $raffle->nextDrawDate()->lte(now()->addDays($remindDays)))
                ->sortBy(fn (Raffle $raffle) => $raffle->nextDrawDate()->timestamp)
                ->values(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $memberCount = $business->uniqueMemberCount();
        abort_unless(FeatureFlags::enabled('raffles'), 403);
        abort_unless(app(\App\Services\PlanLimitService::class)->rafflesEnabled($business), 403);
        if (app(\App\Services\LoopAccess::class)->isPaused($business)) {
            return redirect()->route('billing.show')->with('status', __('loop.loop_paused_safe'));
        }
        if ($memberCount < GrowthSettings::raffleMinMembers()) {
            return redirect()->route('raffles.index')
                ->withErrors(['raffle' => __('loop.raffle_locked_body', [
                    'need' => GrowthSettings::raffleMinMembers(),
                    'have' => $memberCount,
                ])]);
        }

        $maxWinners = GrowthSettings::maxWinnersForMembers($memberCount);

        $prizeTypes = [
            [
                'key' => 'free_item',
                'title' => __('loop.offer_type_free_item_title'),
                'body' => __('loop.raffle_prize_free_body'),
            ],
            [
                'key' => 'percent_off',
                'title' => __('loop.offer_type_percent_off_title'),
                'body' => __('loop.raffle_prize_percent_body'),
            ],
            [
                'key' => 'fixed_off',
                'title' => __('loop.offer_type_fixed_off_title'),
                'body' => __('loop.raffle_prize_fixed_body'),
            ],
            [
                'key' => 'custom',
                'title' => __('loop.offer_type_custom_title'),
                'body' => __('loop.raffle_prize_custom_body'),
            ],
        ];

        return view('raffles.create', [
            'business' => $business,
            'memberCount' => $memberCount,
            'maxWinners' => $maxWinners,
            'maxWinnersPercent' => GrowthSettings::raffleMaxWinnersPercent(),
            'defaultClaimDays' => GrowthSettings::settings()['raffle_default_claim_days'],
            'prizeTypes' => $prizeTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $memberCount = $business->uniqueMemberCount();
        abort_unless(FeatureFlags::enabled('raffles'), 403);
        abort_unless(app(\App\Services\PlanLimitService::class)->rafflesEnabled($business), 403);
        abort_unless(! app(\App\Services\LoopAccess::class)->isPaused($business), 403);
        abort_unless($memberCount >= GrowthSettings::raffleMinMembers(), 403);

        $maxWinners = GrowthSettings::maxWinnersForMembers($memberCount);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'prize_name' => [
                \Illuminate\Validation\Rule::requiredIf(in_array($request->input('prize_type'), ['free_item', 'custom'], true)),
                'nullable',
                'string',
                'max:120',
            ],
            'prize_type' => ['required', 'in:free_item,percent_off,fixed_off,custom'],
            'prize_value' => [
                \Illuminate\Validation\Rule::requiredIf(in_array($request->input('prize_type'), ['percent_off', 'fixed_off'], true)),
                'nullable',
                'numeric',
                'min:0',
            ],
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

        if ($data['prize_type'] === 'percent_off') {
            $request->validate(['prize_value' => ['numeric', 'min:1', 'max:100']]);
        }
        if ($data['prize_type'] === 'fixed_off') {
            $request->validate(['prize_value' => ['numeric', 'min:1']]);
        }

        $data['prize_name'] = $data['prize_name'] ?? null;
        $data['prize_value'] = $data['prize_value'] ?? null;

        if (blank($data['prize_name'])) {
            $data['prize_name'] = match ($data['prize_type']) {
                'percent_off' => __('loop.offer_type_percent_name', ['value' => (int) $data['prize_value']]),
                'fixed_off' => $business->currency.' '.number_format((float) $data['prize_value']).' '.__('loop.off_every_eligible_sale'),
                default => $data['name'],
            };
        }

        if (in_array($data['prize_type'], ['free_item', 'custom'], true) && ($data['prize_value'] === null || $data['prize_value'] === '')) {
            $data['prize_value'] = null;
        }

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
            'raffle' => $raffle->load(['winners.customer', 'winners.membership']),
            'eligibleCount' => app(RaffleService::class)->eligibleCustomers($raffle->business)->count(),
        ]);
    }

    public function live(Request $request, Raffle $raffle): View|RedirectResponse
    {
        $this->authorizeRaffle($request, $raffle);

        $raffle->load(['winners.customer', 'winners.membership', 'business']);

        if ($raffle->remainingWinnerSlots() > 0 && ! $raffle->canDrawNow()) {
            return redirect()->route('raffles.show', $raffle)->withErrors([
                'raffle' => __('loop.raffle_draw_too_soon', [
                    'date' => $raffle->nextDrawDate()->format('j M Y'),
                ]),
            ]);
        }

        return view('raffles.live', [
            'business' => $raffle->business,
            'raffle' => $raffle,
            'remaining' => $raffle->remainingWinnerSlots(),
            'eligibleCount' => app(RaffleService::class)->eligibleCustomers($raffle->business)->count(),
            'drawnWinnerId' => (int) $request->session()->get('drawn_winner_id', 0),
            'spinMs' => GrowthSettings::raffleSpinMs(),
        ]);
    }

    public function draw(Request $request, Raffle $raffle, RaffleService $raffles): RedirectResponse
    {
        $this->authorizeRaffle($request, $raffle);
        abort_unless(FeatureFlags::enabled('raffles'), 403);
        abort_unless(! app(\App\Services\LoopAccess::class)->isPaused($raffle->business), 403);
        $winner = $raffles->drawNext($raffle, $request->user());
        app(\App\Services\DailyNotificationService::class)->notifyRaffleWinner(
            $winner->load(['customer', 'raffle.business', 'membership'])
        );

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
                true,
                ['delay_ms' => GrowthSettings::raffleSpinMs()],
            ),
        ]);
    }

    public function contact(Request $request, Raffle $raffle, \App\Models\RaffleWinner $winner, RaffleService $raffles): RedirectResponse|JsonResponse
    {
        $this->authorizeRaffle($request, $raffle);
        abort_unless($winner->raffle_id === $raffle->id, 404);
        $raffles->markContacted($winner);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'status' => $winner->fresh()->status,
            ]);
        }

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
        app(\App\Services\DailyNotificationService::class)->notifyRaffleClaimed(
            $winner->load(['customer', 'raffle.business', 'membership'])
        );

        return back()->with('confirm', Confirm::make(
            __('loop.raffle_claimed_title'),
            __('loop.raffle_claimed_body', ['name' => $winner->customer->name]),
            __('loop.done'),
            route('raffles.show', $raffle),
            false,
        ));
    }

    public function display(Request $request, Raffle $raffle): View
    {
        $this->authorizeRaffle($request, $raffle);
        $raffle->load(['business']);

        return view('raffles.display', [
            'business' => $raffle->business,
            'raffle' => $raffle,
            'eligibleCount' => $raffle->business->uniqueMemberCount(),
            'boardUrl' => route('raffles.board', $raffle),
            'spinMs' => GrowthSettings::raffleSpinMs(),
        ]);
    }

    public function board(Request $request, Raffle $raffle, RaffleService $raffles): JsonResponse
    {
        $this->authorizeRaffle($request, $raffle);
        $raffle->load(['winners.customer', 'winners.membership', 'business']);
        $latest = $raffle->winners->sortByDesc('draw_order')->first();

        return response()->json([
            'name' => $raffle->name,
            'prize' => $raffle->prize_name,
            'business' => $raffle->business->name,
            'eligible' => $raffles->eligibleCustomers($raffle->business)->count(),
            'winners_count' => (int) $raffle->winners_count,
            'drawn' => $raffle->winners->count(),
            'remaining' => $raffle->remainingWinnerSlots(),
            'status' => $raffle->status,
            'latest' => $latest ? array_merge($latest->publicBoard(), [
                'prize' => $raffle->prize_name,
            ]) : null,
        ]);
    }

    private function authorizeRaffle(Request $request, Raffle $raffle): void
    {
        abort_unless($request->user()->ownedBusiness?->id === $raffle->business_id, 403);
    }
}
