<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'version',
    'audience',
    'status',
    'requires_acceptance',
    'counsel_reviewed',
    'effective_on',
    'retired_on',
    'title_en',
    'title_sw',
    'summary_en',
    'summary_sw',
    'body_en',
    'body_sw',
])]
class LegalDocument extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_RETIRED = 'retired';

    protected function casts(): array
    {
        return [
            'requires_acceptance' => 'boolean',
            'counsel_reviewed' => 'boolean',
            'effective_on' => 'date',
            'retired_on' => 'date',
        ];
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(LegalAcceptance::class);
    }

    public function title(): string
    {
        return app()->getLocale() === 'sw' ? $this->title_sw : $this->title_en;
    }

    public function summary(): string
    {
        return app()->getLocale() === 'sw' ? (string) $this->summary_sw : (string) $this->summary_en;
    }

    public function body(): string
    {
        return app()->getLocale() === 'sw' ? $this->body_sw : $this->body_en;
    }

    public static function current(string $slug): ?self
    {
        return static::query()
            ->where('slug', $slug)
            ->where('status', self::STATUS_PUBLISHED)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function library(): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('status', self::STATUS_PUBLISHED)
            ->orderByDesc('id')
            ->get()
            ->unique('slug')
            ->values();
    }
}
