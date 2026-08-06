<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class PlatformSetting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("platform_setting:{$key}", 60, function () use ($key) {
            return static::query()->where('key', $key)->first();
        });

        return $setting?->value ?? $default;
    }

    public static function putValue(string $key, array $value): self
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("platform_setting:{$key}");

        return $setting;
    }
}
