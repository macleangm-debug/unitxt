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
    'featured_product_name',
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

    public const TYPE_PRODUCT_PUSH = 'product_push';

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

    public function isMain(): bool
    {
        return $this->type === self::TYPE_EARN;
    }

    public function isBonus(): bool
    {
        return ! $this->isMain();
    }

    public function pointsForSpend(float $amount): int
    {
        if ($this->type !== self::TYPE_EARN || ! $this->spend_step || ! $this->points_per_step) {
            return 0;
        }

        $steps = intdiv((int) floor($amount), $this->spend_step);

        // Featured-product bonus is applied at the till when staff confirms the product is in the sale.
        return $steps * $this->points_per_step;
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
            self::TYPE_EARN => __('loop.rule_earn', [
                'currency' => $currency,
                'step' => number_format($this->spend_step),
                'points' => $this->points_per_step,
            ]),
            self::TYPE_PRODUCT_PUSH => $this->featured_product_name
                ? __('loop.rule_featured_product', [
                    'product' => $this->featured_product_name,
                    'points' => $this->bonus_points,
                ])
                : $this->displayName(),
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
        // Always prefer the saved name so personalized campaigns (e.g. "Real Burger Points") stick.
        if (filled($this->name)) {
            return $this->name;
        }

        return \App\Support\CampaignTemplates::nameFor($this->template_key, 'Campaign');
    }

    public function displayDescription(): ?string
    {
        if (filled($this->description)) {
            return $this->description;
        }

        return \App\Support\CampaignTemplates::descriptionFor($this->template_key, null);
    }

    public function scheduleLabel(): string
    {
        $start = $this->starts_at?->format('d M Y');
        if (! $start) {
            return __('loop.open_ended');
        }

        if ($this->ends_at) {
            return __('loop.campaign_date_range', [
                'from' => $start,
                'to' => $this->ends_at->format('d M Y'),
            ]);
        }

        return __('loop.campaign_ongoing_from', ['from' => $start]);
    }
}
