<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'country_code',
    'phone',
    'code',
    'expires_at',
    'consumed_at',
])]
class PhoneOtp extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function isValid(string $code): bool
    {
        return $this->consumed_at === null
            && $this->expires_at->isFuture()
            && hash_equals($this->code, $code);
    }
}
