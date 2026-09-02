<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'business_id',
    'name',
])]
class MemberGroup extends Model
{
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'member_group_user')->withTimestamps();
    }
}
