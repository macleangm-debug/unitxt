<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\GameService;
use App\Support\Confirm;
use App\Support\GameSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request, GameService $games): View
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);

        $limits = app(\App\Services\PlanLimitService::class);
        $planAllows = $limits->gamesEnabled($business);

        return view('games.index', [
            'business' => $business,
            'games' => GameSettings::tablesReady()
                ? $business->games()->withCount(['plays', 'prizes'])->latest()->get()
                : collect(),
            'planLocked' => ! $planAllows,
            'platformOff' => ! GameSettings::engineOn(),
            'paused' => app(\App\Services\LoopAccess::class)->isPaused($business),
            'recommend' => $games->recommendFor($business),
        ]);
    }

    public function create(Request $request, GameService $games): View|RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        abort_unless(GameSettings::engineOn(), 403);
        abort_unless(app(\App\Services\PlanLimitService::class)->gamesEnabled($business), 403);
        if (app(\App\Services\LoopAccess::class)->isPaused($business)) {
            return redirect()->route('billing.show')->with('status', __('loop.loop_paused_safe'));
        }

        $recommend = $games->recommendFor($business);
        $settings = GameSettings::settings();

        return view('games.create', [
            'business' => $business,
            'recommend' => $recommend,
            'types' => GameSettings::types(),
            'settings' => $settings,
            'prizeKinds' => $settings['allowed_prize_kinds'],
        ]);
    }

    public function store(Request $request, GameService $games): RedirectResponse
    {
        $business = $request->user()->ownedBusiness;
        abort_unless($business && $request->user()->isOwner(), 403);
        abort_unless(GameSettings::engineOn(), 403);
        abort_unless(app(\App\Services\PlanLimitService::class)->gamesEnabled($business), 403);
        if (app(\App\Services\LoopAccess::class)->isPaused($business)) {
            return redirect()->route('billing.show')->with('status', __('loop.loop_paused_safe'));
        }

        $settings = GameSettings::settings();
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', GameSettings::types())],
            'name' => ['nullable', 'string', 'max:80'],
            'qualify_mode' => ['required', 'in:spend,visits,both'],
            'spend_threshold' => ['nullable', 'integer', 'min:500'],
            'visit_threshold' => ['nullable', 'integer', 'min:2', 'max:50'],
            'play_limit' => ['required', 'in:daily,transaction,game'],
            'win_mode' => ['required', 'in:automatic,odds,spread'],
            'odds_every' => ['nullable', 'integer', 'min:2', 'max:100'],
            'spread_count' => ['nullable', 'integer', 'min:1', 'max:500'],
            'spread_period' => ['nullable', 'in:day,week'],
            'expected_plays' => ['nullable', 'integer', 'min:20', 'max:20000'],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'claim_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'prizes' => ['required', 'array', 'min:1'],
            'prizes.*.kind' => ['required', 'in:'.implode(',', $settings['allowed_prize_kinds'])],
            'prizes.*.name' => ['nullable', 'string', 'max:80'],
            'prizes.*.quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'prizes.*.points_value' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'prizes.*.percent_value' => ['nullable', 'integer', 'min:1', 'max:100'],
            'prizes.*.unit_cost' => ['nullable', 'integer', 'min:0'],
        ]);

        if (in_array($data['qualify_mode'], ['spend', 'both'], true) && empty($data['spend_threshold'])) {
            return back()->withInput()->withErrors(['spend_threshold' => __('loop.game_spend_required')]);
        }
        if (in_array($data['qualify_mode'], ['visits', 'both'], true) && empty($data['visit_threshold'])) {
            return back()->withInput()->withErrors(['visit_threshold' => __('loop.game_visits_required')]);
        }
        if ($data['win_mode'] === 'odds' && empty($data['odds_every'])) {
            return back()->withInput()->withErrors(['odds_every' => __('loop.game_odds_required')]);
        }
        if ($data['win_mode'] === 'spread' && empty($data['spread_count'])) {
            return back()->withInput()->withErrors(['spread_count' => __('loop.game_spread_required')]);
        }

        $data['name'] = filled($data['name'] ?? null)
            ? $data['name']
            : __('loop.game_name_'.$data['type'], ['business' => $business->name]);
        $game = $games->create($business, (int) $request->user()->id, $data, $data['prizes']);

        return redirect()->route('games.show', $game)->with('confirm', Confirm::make(
            __('loop.game_launched_title'),
            __('loop.game_launched_body', ['name' => $game->name]),
            __('loop.done'),
            route('games.show', $game),
        ));
    }

    public function show(Request $request, Game $game, GameService $games): View
    {
        $this->authorizeGame($request, $game);

        return view('games.show', [
            'business' => $game->business,
            'game' => $game->load(['prizes', 'plays' => fn ($q) => $q->latest()->limit(20)]),
            'stats' => $games->analytics($game),
            'paused' => app(\App\Services\LoopAccess::class)->isPaused($game->business),
        ]);
    }

    private function authorizeGame(Request $request, Game $game): void
    {
        abort_unless($request->user()->ownedBusiness?->id === $game->business_id, 403);
    }
}
