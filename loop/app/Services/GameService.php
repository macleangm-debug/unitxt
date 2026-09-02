<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Game;
use App\Models\GamePlay;
use App\Models\GamePrize;
use App\Models\Visit;
use App\Support\GameSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GameService
{
    /**
     * @return array<string, mixed>
     */
    public function recommendFor(Business $business): array
    {
        $settings = GameSettings::settings();
        $earn = $business->campaigns()
            ->where('type', 'earn')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
        $typical = (int) ($earn?->spend_step ?: 10000);
        $spend = (int) max(1000, round(($typical * (float) $settings['spend_multiplier']) / 1000) * 1000);
        $visits = (int) $settings['recommended_visit_threshold'];

        return [
            'qualify_mode' => $settings['default_qualify'],
            'spend_threshold' => $spend,
            'visit_threshold' => $visits,
            'play_limit' => $settings['default_play_frequency'],
            'expected_plays' => (int) $settings['expected_plays'],
            'win_rate' => (int) $settings['recommended_win_rate'],
            'typical_spend' => $typical,
            'claim_days' => (int) $settings['claim_days'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $prizes
     */
    public function create(Business $business, int $userId, array $data, array $prizes): Game
    {
        $settings = GameSettings::settings();
        $type = (string) $data['type'];
        abort_unless(in_array($type, GameSettings::types(), true), 422);

        $starts = $data['starts_at'];
        $ends = $data['ends_at'];
        $maxDays = (int) $settings['max_duration_days'];
        if (\Illuminate\Support\Carbon::parse($starts)->diffInDays(\Illuminate\Support\Carbon::parse($ends)) > $maxDays) {
            throw ValidationException::withMessages([
                'ends_at' => __('loop.game_duration_too_long', ['days' => $maxDays]),
            ]);
        }

        return DB::transaction(function () use ($business, $userId, $data, $prizes, $type, $settings) {
            $game = Game::create([
                'business_id' => $business->id,
                'created_by' => $userId,
                'type' => $type,
                'name' => $data['name'] ?: __('loop.game_name_'.$type, ['business' => $business->name]),
                'status' => 'live',
                'qualify_mode' => $data['qualify_mode'],
                'spend_threshold' => in_array($data['qualify_mode'], ['spend', 'both'], true) ? (int) $data['spend_threshold'] : null,
                'visit_threshold' => in_array($data['qualify_mode'], ['visits', 'both'], true) ? (int) $data['visit_threshold'] : null,
                'play_limit' => $data['play_limit'],
                'win_mode' => $data['win_mode'],
                'odds_every' => $data['win_mode'] === 'odds' ? (int) $data['odds_every'] : null,
                'spread_count' => $data['win_mode'] === 'spread' ? (int) $data['spread_count'] : null,
                'spread_period' => $data['win_mode'] === 'spread' ? ($data['spread_period'] ?? 'day') : null,
                'expected_plays' => (int) ($data['expected_plays'] ?: $settings['expected_plays']),
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'claim_days' => (int) ($data['claim_days'] ?: $settings['claim_days']),
                'is_active' => true,
            ]);

            foreach (array_values($prizes) as $i => $row) {
                $kind = (string) ($row['kind'] ?? 'free_item');
                if (! in_array($kind, $settings['allowed_prize_kinds'], true)) {
                    $kind = 'custom';
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    $name = match ($kind) {
                        'percent' => __('loop.game_prize_percent_name', ['n' => (int) ($row['percent_value'] ?? 0)]),
                        'points' => __('loop.game_prize_points_name', ['n' => (int) ($row['points_value'] ?? 0)]),
                        default => '',
                    };
                }
                if ($name === '' || (int) ($row['quantity'] ?? 0) < 1) {
                    continue;
                }
                if ($kind === 'percent' && (int) ($row['percent_value'] ?? 0) < 1) {
                    continue;
                }
                if ($kind === 'points' && (int) ($row['points_value'] ?? 0) < 1) {
                    continue;
                }
                GamePrize::create([
                    'game_id' => $game->id,
                    'kind' => $kind,
                    'name' => $name,
                    'quantity' => (int) $row['quantity'],
                    'awarded_count' => 0,
                    'points_value' => $kind === 'points' ? max(1, (int) ($row['points_value'] ?? 0)) : null,
                    'percent_value' => $kind === 'percent' ? max(1, min(100, (int) ($row['percent_value'] ?? 0))) : null,
                    'unit_cost' => filled($row['unit_cost'] ?? null) ? (int) $row['unit_cost'] : null,
                    'sort_order' => $i,
                ]);
            }

            if (! $game->prizes()->exists()) {
                throw ValidationException::withMessages([
                    'prizes' => __('loop.game_what_they_win'),
                ]);
            }

            return $game->fresh(['prizes']);
        });
    }

    public function grantForVisit(Visit $visit): ?GamePlay
    {
        if (! GameSettings::tablesReady()) {
            return null;
        }

        $business = $visit->business ?? Business::query()->find($visit->business_id);
        if (! $business || ! app(PlanLimitService::class)->gamesEnabled($business)) {
            return null;
        }
        if (app(LoopAccess::class)->isPaused($business)) {
            return null;
        }

        $granted = null;
        $games = $business->games()->live()->with('prizes')->orderBy('id')->get();
        foreach ($games as $game) {
            $play = $this->tryGrant($game, $visit);
            if ($play) {
                $granted ??= $play->load('game');
            }
        }

        return $granted;
    }

    public function tryGrant(Game $game, Visit $visit): ?GamePlay
    {
        if (! $game->isLive() || (int) $visit->business_id !== (int) $game->business_id) {
            return null;
        }
        if (! $this->visitQualifies($game, $visit)) {
            return null;
        }
        if ($this->blockedByLimit($game, $visit)) {
            return null;
        }

        return GamePlay::create([
            'game_id' => $game->id,
            'business_id' => $game->business_id,
            'customer_id' => $visit->customer_id,
            'membership_id' => $visit->membership_id,
            'visit_id' => $visit->id,
            'status' => 'pending',
            'eligible_at' => now(),
        ]);
    }

    public function visitQualifies(Game $game, Visit $visit): bool
    {
        $spendOk = (float) $visit->amount_spent >= (int) $game->spend_threshold;
        $visits = Visit::query()
            ->where('membership_id', $visit->membership_id)
            ->count();

        return match ($game->qualify_mode) {
            'visits' => $game->visit_threshold > 0 && $visits > 0 && ($visits % (int) $game->visit_threshold === 0),
            'both' => $spendOk && $visits >= (int) $game->visit_threshold,
            default => $spendOk,
        };
    }

    public function blockedByLimit(Game $game, Visit $visit): bool
    {
        $query = GamePlay::query()
            ->where('game_id', $game->id)
            ->where('customer_id', $visit->customer_id);

        if ($game->play_limit === 'transaction') {
            return (clone $query)->where('visit_id', $visit->id)->exists();
        }
        if ($game->play_limit === 'game') {
            return $query->exists();
        }

        return $query->whereDate('eligible_at', today())->exists();
    }

    public function reveal(GamePlay $play): GamePlay
    {
        if (! $play->isPending()) {
            return $play->fresh(['game.prizes', 'prize', 'customer', 'business']);
        }

        return DB::transaction(function () use ($play) {
            $play = GamePlay::query()->lockForUpdate()->findOrFail($play->id);
            if (! $play->isPending()) {
                return $play->fresh(['game.prizes', 'prize', 'customer', 'business']);
            }

            $game = Game::query()->lockForUpdate()->with('prizes')->findOrFail($play->game_id);
            [$outcome, $prize] = $this->decideOutcome($game);

            $play->status = 'played';
            $play->outcome = $outcome;
            $play->played_at = now();
            if ($prize) {
                $play->prize_id = $prize->id;
                $play->claim_by = now()->addDays(max(1, (int) $game->claim_days));
                $prize->awarded_count = (int) $prize->awarded_count + 1;
                $prize->save();
            }
            $play->save();

            return $play->fresh(['game.prizes', 'prize', 'customer', 'business']);
        });
    }

    /**
     * @return array{0: string, 1: ?GamePrize}
     */
    public function decideOutcome(Game $game): array
    {
        $available = $game->prizes->filter(fn (GamePrize $prize) => $prize->remaining() > 0)->values();
        if ($available->isEmpty()) {
            return ['no_win', null];
        }

        $settings = GameSettings::settings();
        $maxRate = ((int) $settings['max_win_rate']) / 100;
        $played = $game->plays()->whereNotNull('played_at')->count();
        $won = $game->plays()->where('outcome', 'win')->count();
        if ($played > 8 && ($won / max(1, $played)) >= $maxRate) {
            return ['no_win', null];
        }

        if ($game->win_mode === 'spread') {
            $periodStart = ($game->spread_period ?? 'day') === 'week'
                ? now()->startOfWeek()
                : now()->startOfDay();
            $wonInPeriod = $game->plays()
                ->where('outcome', 'win')
                ->where('played_at', '>=', $periodStart)
                ->count();
            if ($wonInPeriod >= max(1, (int) $game->spread_count)) {
                return ['no_win', null];
            }
        }

        $winChance = $this->winChance($game, $available->sum(fn (GamePrize $p) => $p->remaining()));
        $roll = mt_rand(1, 10000) / 10000;
        if ($roll > $winChance) {
            return ['no_win', null];
        }

        $pool = [];
        foreach ($available as $prize) {
            for ($i = 0; $i < $prize->remaining(); $i++) {
                $pool[] = $prize;
            }
        }
        $picked = $pool[array_rand($pool)];

        return ['win', $picked];
    }

    public function winChance(Game $game, int $remainingPrizes): float
    {
        $settings = GameSettings::settings();
        $cap = ((int) $settings['max_win_rate']) / 100;

        if ($game->win_mode === 'odds' && $game->odds_every) {
            return min($cap, 1 / max(1, (int) $game->odds_every));
        }

        $played = $game->plays()->whereNotNull('played_at')->count();
        $left = max(1, (int) $game->expected_plays - $played);

        return min($cap, $remainingPrizes / $left);
    }

    public function claim(GamePlay $play, ?\App\Models\User $by = null): GamePlay
    {
        if ($play->status === 'claimed') {
            return $play;
        }
        abort_unless($play->isWin() && $play->prize, 403);

        return DB::transaction(function () use ($play, $by) {
            $play = GamePlay::query()->lockForUpdate()->with(['prize', 'membership'])->findOrFail($play->id);
            if ($play->status === 'claimed') {
                return $play;
            }
            $play->status = 'claimed';
            $play->claimed_at = now();
            $play->save();

            if ($play->prize?->kind === 'points' && (int) $play->prize->points_value > 0 && $play->membership) {
                app(PointsService::class)->earn(
                    $play->membership,
                    (int) $play->prize->points_value,
                    $by,
                    $play->visit,
                    __('loop.game_points_from_win', ['game' => $play->game?->name ?? '']),
                );
            }

            return $play->fresh(['prize', 'game', 'customer']);
        });
    }

    /**
     * @return array<string, int|float>
     */
    public function analytics(Game $game): array
    {
        $plays = $game->plays();
        $played = (clone $plays)->whereNotNull('played_at')->count();
        $won = (clone $plays)->where('outcome', 'win')->count();
        $claimed = (clone $plays)->where('status', 'claimed')->count();
        $sales = (float) Visit::query()
            ->whereIn('id', $game->plays()->whereNotNull('visit_id')->pluck('visit_id'))
            ->sum('amount_spent');

        return [
            'played' => $played,
            'won' => $won,
            'claimed' => $claimed,
            'pending' => (clone $plays)->where('status', 'pending')->count(),
            'qualifying_sales' => $sales,
        ];
    }

    public function pendingForCustomer(int $customerId)
    {
        if (! GameSettings::tablesReady()) {
            return collect();
        }

        return GamePlay::query()
            ->with(['game.business', 'prize'])
            ->where('customer_id', $customerId)
            ->where('status', 'pending')
            ->latest('id')
            ->get();
    }
}
