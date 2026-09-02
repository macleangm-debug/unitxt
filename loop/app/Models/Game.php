<?php

namespace App\Models;

use App\Support\GameSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'business_id',
    'created_by',
    'type',
    'name',
    'status',
    'qualify_mode',
    'spend_threshold',
    'visit_threshold',
    'play_limit',
    'win_mode',
    'odds_every',
    'spread_count',
    'spread_period',
    'expected_plays',
    'starts_at',
    'ends_at',
    'claim_days',
    'is_active',
])]
class Game extends Model
{
    protected function casts(): array
    {
        return [
            'spend_threshold' => 'integer',
            'visit_threshold' => 'integer',
            'odds_every' => 'integer',
            'spread_count' => 'integer',
            'expected_plays' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'claim_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(GamePrize::class)->orderBy('sort_order')->orderBy('id');
    }

    public function plays(): HasMany
    {
        return $this->hasMany(GamePlay::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('status', 'live')
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now());
    }

    public function isLive(): bool
    {
        return $this->is_active
            && $this->status === 'live'
            && $this->starts_at->toDateString() <= now()->toDateString()
            && $this->ends_at->toDateString() >= now()->toDateString();
    }

    public function typeLabel(): string
    {
        return __('loop.game_type_'.$this->type);
    }

    public function qualifyLine(): string
    {
        $currency = $this->business?->currency ?? 'TZS';
        if ($this->qualify_mode === 'visits') {
            return __('loop.game_qualify_visits_line', ['count' => $this->visit_threshold]);
        }
        if ($this->qualify_mode === 'both') {
            return __('loop.game_qualify_both_line', [
                'count' => $this->visit_threshold,
                'currency' => $currency,
                'amount' => number_format((int) $this->spend_threshold),
            ]);
        }

        return __('loop.game_qualify_spend_line', [
            'currency' => $currency,
            'amount' => number_format((int) $this->spend_threshold),
        ]);
    }

    public function remainingPrizes(): int
    {
        return (int) $this->prizes->sum(fn (GamePrize $prize) => $prize->remaining());
    }

    public function aboutWinRate(): int
    {
        $prizes = max(0, (int) $this->prizes->sum('quantity'));
        $plays = max(1, (int) $this->expected_plays);
        if ($this->win_mode === 'odds' && $this->odds_every) {
            return (int) round(100 / max(1, (int) $this->odds_every));
        }

        return (int) min(GameSettings::settings()['max_win_rate'], round(($prizes / $plays) * 100));
    }
}
