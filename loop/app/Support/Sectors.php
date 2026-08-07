<?php

namespace App\Support;

use App\Models\PlatformSetting;

class Sectors
{
    public const KEY = 'sectors';

    public const OPTIONS = [
        'restaurants' => 'Restaurants',
        'coffee' => 'Coffee & cafés',
        'fast_food' => 'Fast food',
        'fashion' => 'Fashion & apparel',
        'beauty' => 'Beauty & salon',
        'health' => 'Health & pharmacy',
        'ppe' => 'PPE & safety',
        'grocery' => 'Grocery & minimart',
        'electronics' => 'Electronics',
        'retail' => 'General retail',
        'automotive' => 'Automotive',
        'fitness' => 'Fitness & wellness',
        'hospitality' => 'Hotels & lodging',
        'education' => 'Education & tutoring',
        'services' => 'Professional services',
        'other' => 'Other',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $stored = PlatformSetting::getValue(self::KEY, null);
        if (is_array($stored) && $stored !== []) {
            $out = [];
            foreach ($stored as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $key = (string) ($row['key'] ?? '');
                $label = (string) ($row['label'] ?? '');
                if ($key === '' || $label === '') {
                    continue;
                }
                $out[$key] = $label;
            }
            if ($out !== []) {
                if (! isset($out['other'])) {
                    $out['other'] = self::OPTIONS['other'];
                }

                return $out;
            }
        }

        return self::OPTIONS;
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public static function list(): array
    {
        return collect(self::all())
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{key?: string, label?: string}>|array<string, string>  $input
     * @return list<array{key: string, label: string}>
     */
    public static function normalizeInput(array $input): array
    {
        $rows = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $k = strtolower(preg_replace('/[^a-z0-9_]+/', '_', (string) ($value['key'] ?? '')) ?? '');
                $label = trim((string) ($value['label'] ?? ''));
            } else {
                $k = is_string($key) ? strtolower(preg_replace('/[^a-z0-9_]+/', '_', $key) ?? '') : '';
                $label = trim((string) $value);
            }
            $k = trim($k, '_');
            if ($k === '' || $label === '') {
                continue;
            }
            $rows[$k] = ['key' => $k, 'label' => $label];
        }

        if (! isset($rows['other'])) {
            $rows['other'] = ['key' => 'other', 'label' => self::OPTIONS['other']];
        }

        return array_values($rows);
    }

    public static function label(?string $key, ?string $custom = null): string
    {
        if ($key === 'other' && filled($custom)) {
            return $custom;
        }

        return self::all()[$key] ?? 'Other';
    }

    public static function keysRule(): string
    {
        return 'in:'.implode(',', array_keys(self::all()));
    }
}
