<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'yearly_fee',
    'status',
    'sort_order',
])]
class PlatformSenderId extends Model
{
    protected function casts(): array
    {
        return [
            'yearly_fee' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function senderIds(): HasMany
    {
        return $this->hasMany(SenderId::class);
    }
}
