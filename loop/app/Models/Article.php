<?php

namespace App\Models;

use App\Support\Countries;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'slug',
    'title_en',
    'title_sw',
    'excerpt_en',
    'excerpt_sw',
    'body_en',
    'body_sw',
    'image_path',
    'country',
    'audience',
    'published_at',
    'sort_order',
])]
class Article extends Model
{
    public const AUDIENCE_MEMBERS = 'members';

    public const AUDIENCE_OWNERS = 'owners';

    public const AUDIENCE_ALL = 'all';

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    public function localized(string $field): string
    {
        $locale = app()->getLocale() === 'sw' ? 'sw' : 'en';
        $primary = trim((string) ($this->{$field.'_'.$locale} ?? ''));
        if ($primary !== '') {
            return $primary;
        }

        $fallback = $locale === 'sw' ? 'en' : 'sw';

        return trim((string) ($this->{$field.'_'.$fallback} ?? ''));
    }

    public function title(): string
    {
        return $this->localized('title');
    }

    public function excerpt(): string
    {
        $excerpt = $this->localized('excerpt');
        if ($excerpt !== '') {
            return $excerpt;
        }

        return Str::limit(strip_tags($this->body()), 160);
    }

    public function body(): string
    {
        return $this->localized('body');
    }

    public function bodyHtml(): string
    {
        $escaped = e($this->body());
        $blocks = preg_split("/\n{2,}/", $escaped) ?: [];

        return collect($blocks)
            ->map(fn (string $block) => '<p class="mb-3 last:mb-0">'.nl2br(trim($block), false).'</p>')
            ->implode('');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function countryLabel(): string
    {
        if (! $this->country) {
            return __('loop.all_countries');
        }

        $meta = Countries::OPTIONS[$this->country] ?? null;

        return $meta ? ($meta['flag'].' '.$meta['name']) : $this->country;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeVisibleTo(Builder $query, ?User $user = null, ?string $country = null): Builder
    {
        $country = $country ?: ($user?->country ?: session('preferred_country', 'TZ'));

        return $query->published()
            ->whereIn('audience', [self::AUDIENCE_MEMBERS, self::AUDIENCE_ALL])
            ->where(function (Builder $inner) use ($country) {
                $inner->whereNull('country')->orWhere('country', $country);
            });
    }

    public static function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'story';
        $slug = $base;
        $i = 1;
        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
