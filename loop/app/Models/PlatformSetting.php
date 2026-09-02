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
        return Cache::remember("platform_setting:{$key}", 60, function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function putValue(string $key, array $value): self
    {
        $previous = static::query()->where('key', $key)->first();
        $old = $previous?->value;

        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("platform_setting:{$key}");

        try {
            if ($old != $value) {
                SettingAudit::query()->create([
                    'user_id' => auth()->id(),
                    'setting_key' => $key,
                    'old_value' => is_array($old) ? $old : null,
                    'new_value' => $value,
                ]);
            }
        } catch (\Throwable) {
            // Audits must never block saving platform law.
        }

        return $setting;
    }
}
