<?php

namespace App\Http\Controllers;

use App\Models\GamePlay;
use App\Services\GameService;
use App\Support\Confirm;
use App\Support\GameSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GamePlayController extends Controller
{
    public function show(Request $request, GamePlay $play): View
    {
        $this->authorizePlay($request, $play);
        $play->load(['game.prizes', 'prize', 'business', 'customer']);

        return view('games.play', [
            'play' => $play,
            'game' => $play->game,
            'business' => $play->business,
            'revealMs' => (int) GameSettings::settings()['play_reveal_ms'],
            'justPlayed' => (bool) $request->session()->get('game_just_played'),
        ]);
    }

    public function reveal(Request $request, GamePlay $play, GameService $games): RedirectResponse
    {
        $this->authorizePlay($request, $play);
        abort_unless(GameSettings::engineOn(), 403);
        if (app(\App\Services\LoopAccess::class)->isPaused($play->business)) {
            return back()->with('status', __('loop.game_paused'));
        }

        $play = $games->reveal($play);
        $revealMs = (int) GameSettings::settings()['play_reveal_ms'];

        $confirm = $play->isWin()
            ? Confirm::make(
                __('loop.game_you_won_title'),
                __('loop.game_you_won_body', ['prize' => $play->prize?->name ?? '']),
                __('loop.done'),
                route('games.play', $play),
                true,
                ['delay_ms' => $revealMs],
            )
            : Confirm::make(
                __('loop.game_better_luck_title'),
                __('loop.game_better_luck_body'),
                __('loop.see_my_rewards'),
                route('dashboard'),
                false,
                ['delay_ms' => $revealMs],
            );

        return redirect()->route('games.play', $play)->with([
            'game_just_played' => true,
            'confirm' => $confirm,
        ]);
    }

    public function claim(Request $request, GamePlay $play, GameService $games): RedirectResponse
    {
        $this->authorizePlay($request, $play);
        $games->claim($play, $request->user());

        return back()->with('confirm', Confirm::make(
            __('loop.game_claimed_title'),
            __('loop.game_claimed_body', ['prize' => $play->prize?->name ?? '']),
            __('loop.done'),
            route('games.play', $play),
            false,
        ));
    }

    private function authorizePlay(Request $request, GamePlay $play): void
    {
        $user = $request->user();
        if ((int) $user->id === (int) $play->customer_id) {
            return;
        }
        $workplace = $user->ownedBusiness ?? $user->workplace();
        abort_unless($workplace && (int) $workplace->id === (int) $play->business_id, 403);
        abort_unless($user->isOwner() || $user->canUseTill(), 403);
    }
}
