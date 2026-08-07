<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'business_id',
    'name',
    'type',
    'description',
    'spend_step',
    'points_per_step',
    'bonus_points',
    'streak_target',
    'streak_period',
    'max_earns_per_day',
    'starts_at',
    'ends_at',
    'is_active',
    'template_key',
])]
class Campaign extends Model
{
    public const TYPE_EARN = 'earn';

    public const TYPE_BIRTHDAY = 'birthday';

    public const TYPE_WELCOME = 'welcome';

    public const TYPE_STREAK = 'streak';

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
            'spend_step' => 'integer',
            'points_per_step' => 'integer',
            'bonus_points' => 'integer',
            'streak_target' => 'integer',
            'max_earns_per_day' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class);
    }

    public function rewards(): BelongsToMany
    {
        return $this->belongsToMany(Reward::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = Carbon::today();

        return $query
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $today)
            ->where(function (Builder $builder) use ($today): void {
                $builder->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today);
            });
    }

    public function isCurrentlyActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = Carbon::today();

        if ($this->starts_at->gt($today)) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->gte($today);
    }

    public function pointsForSpend(float $amount): int
    {
        if (! in_array($this->type, [self::TYPE_EARN, 'product_push'], true) || ! $this->spend_step || ! $this->points_per_step) {
            return 0;
        }

        $steps = intdiv((int) floor($amount), $this->spend_step);

        return ($steps * $this->points_per_step) + $this->bonus_points;
    }

    public function currencyPerPoint(): float
    {
        if (! $this->spend_step || ! $this->points_per_step) {
            return 0;
        }

        return $this->spend_step / $this->points_per_step;
    }

    public function ruleSummary(string $currency = 'TZS'): string
    {
        return match ($this->type) {
            self::TYPE_EARN, 'product_push' => __('loop.rule_earn', [
                'currency' => $currency,
                'step' => number_format($this->spend_step),
                'points' => $this->points_per_step,
            ]),
            self::TYPE_BIRTHDAY => __('loop.rule_birthday', ['points' => $this->bonus_points]),
            self::TYPE_WELCOME => __('loop.rule_welcome', ['points' => $this->bonus_points]),
            self::TYPE_STREAK => __('loop.rule_streak_detail', [
                'points' => $this->bonus_points,
                'target' => $this->streak_target ?: 3,
                'period' => __('loop.streak_period_'.($this->streak_period ?: 'week')),
            ]),
            default => $this->displayName(),
        };
    }

    public function displayName(): string
    {
        return \App\Support\CampaignTemplates::nameFor($this->template_key, $this->name);
    }

    public function displayDescription(): ?string
    {
        return \App\Support\CampaignTemplates::descriptionFor($this->template_key, $this->description);
    }
}
