<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'business_id',
    'audience',
    'type',
    'dedupe_key',
    'title_key',
    'body_key',
    'params',
    'cta_key',
    'url',
    'tone',
    'read_at',
    'for_date',
])]
class InAppNotification extends Model
{
    protected function casts(): array
    {
        return [
            'params' => 'array',
            'read_at' => 'datetime',
            'for_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function title(): string
    {
        return __($this->title_key, $this->params ?? []);
    }

    public function body(): string
    {
        return __($this->body_key, $this->params ?? []);
    }

    public function cta(): ?string
    {
        return $this->cta_key ? __($this->cta_key, $this->params ?? []) : null;
    }
}
