<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'query_key',
    'query',
    'hits',
    'first_seen_at',
    'last_seen_at',
])]
class SectorSearchMiss extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public static function record(string $query): ?self
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? '');
        if (mb_strlen($query) < 2 || mb_strlen($query) > 80) {
            return null;
        }

        $key = Str::lower($query);
        $now = now();
        $row = static::query()->firstOrNew(['query_key' => $key]);
        $row->query = $query;
        $row->hits = (int) $row->hits + 1;
        $row->first_seen_at = $row->first_seen_at ?? $now;
        $row->last_seen_at = $now;
        $row->save();

        return $row;
    }
}
