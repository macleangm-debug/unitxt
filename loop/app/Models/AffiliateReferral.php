<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'affiliate_id',
    'business_id',
    'code_used',
    'status',
    'plan_amount',
    'discount_amount',
    'net_amount',
    'commission_percent',
    'commission_amount',
    'qualified_at',
    'attribution_ends_at',
])]
class AffiliateReferral extends Model
{
    protected function casts(): array
    {
        return [
            'plan_amount' => 'integer',
            'discount_amount' => 'integer',
            'net_amount' => 'integer',
            'commission_percent' => 'integer',
            'commission_amount' => 'integer',
            'qualified_at' => 'datetime',
            'attribution_ends_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function isAttributionActive(): bool
    {
        return $this->attribution_ends_at === null || $this->attribution_ends_at->isFuture();
    }
}
