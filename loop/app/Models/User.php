<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'first_name',
    'last_name',
    'country_code',
    'country',
    'city',
    'interests',
    'phone',
    'email',
    'password',
    'pin_hash',
    'birth_date',
    'birth_month',
    'birth_day',
    'role',
    'business_id',
    'shop_id',
    'must_change_password',
    'is_active',
    'profile_completed',
    'phone_verified_at',
    'email_verified_at',
    'locale',
])]
#[Hidden(['password', 'pin_hash', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_FRONT_DESK = 'front_desk';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_AFFILIATE = 'affiliate';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'birth_date' => 'date',
            'birth_month' => 'integer',
            'birth_day' => 'integer',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'profile_completed' => 'boolean',
            'interests' => 'array',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }

    public function getFullPhoneAttribute(): string
    {
        return $this->country_code.' '.$this->phone;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isFrontDesk(): bool
    {
        return $this->role === self::ROLE_FRONT_DESK;
    }

    public function isStaff(): bool
    {
        return $this->isOwner() || $this->isFrontDesk();
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAffiliate(): bool
    {
        return $this->role === self::ROLE_AFFILIATE;
    }

    public function affiliateProfile(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function canManageCampaigns(): bool
    {
        return $this->isOwner();
    }

    public function canUseTill(): bool
    {
        return $this->isStaff() && $this->is_active;
    }

    public function ownedBusiness(): HasOne
    {
        return $this->hasOne(Business::class, 'owner_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function workplace(): ?Business
    {
        if ($this->isOwner()) {
            return $this->ownedBusiness;
        }

        return $this->business;
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'customer_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'customer_id');
    }
}
