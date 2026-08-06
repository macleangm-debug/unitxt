<?php

namespace App\Models;

use App\Support\Sectors;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'owner_id',
    'name',
    'slug',
    'sector',
    'sector_other',
    'country',
    'currency',
    'city',
    'branch_count',
    'description',
    'logo_path',
    'is_active',
    'onboarding_completed_at',
])]
class Business extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'onboarding_completed_at' => 'datetime',
            'branch_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Business $business): void {
            if (blank($business->slug)) {
                $business->slug = Str::slug($business->name).'-'.Str::lower(Str::random(4));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', [User::ROLE_OWNER, User::ROLE_FRONT_DESK]);
    }

    public function frontDeskStaff(): HasMany
    {
        return $this->hasMany(User::class)->where('role', User::ROLE_FRONT_DESK);
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function sectorLabel(): string
    {
        return Sectors::label($this->sector, $this->sector_other);
    }
}
