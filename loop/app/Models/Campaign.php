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
    'description',
    'points_per_visit',
    'bonus_points',
    'max_visits_per_day',
    'starts_at',
    'ends_at',
    'is_active',
])]
class Campaign extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
            'points_per_visit' => 'integer',
            'bonus_points' => 'integer',
            'max_visits_per_day' => 'integer',
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

    public function pointsForVisit(): int
    {
        return $this->points_per_visit + $this->bonus_points;
    }
}
