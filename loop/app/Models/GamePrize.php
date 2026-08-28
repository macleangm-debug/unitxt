<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'game_id',
    'kind',
    'name',
    'quantity',
    'awarded_count',
    'points_value',
    'percent_value',
    'unit_cost',
    'sort_order',
])]
class GamePrize extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'awarded_count' => 'integer',
            'points_value' => 'integer',
            'percent_value' => 'integer',
            'unit_cost' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function plays(): HasMany
    {
        return $this->hasMany(GamePlay::class, 'prize_id');
    }

    public function remaining(): int
    {
        return max(0, (int) $this->quantity - (int) $this->awarded_count);
    }
}
