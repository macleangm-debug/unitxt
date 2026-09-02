<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoopBackRequest extends Model
{
    protected $fillable = [
        'business_id',
        'customer_id',
        'points_snapshot',
        'close_to_reward',
        'reward_ready',
    ];

    protected function casts(): array
    {
        return [
            'points_snapshot' => 'integer',
            'close_to_reward' => 'boolean',
            'reward_ready' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
