<?php

namespace App\Support;

class CampaignTemplates
{
    public const INTENTION_EARN = 'earn_points';

    public const INTENTION_PRODUCT = 'product';

    public const INTENTION_RETENTION = 'retention';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'everyday_earn' => [
                'intention' => self::INTENTION_EARN,
                'type' => 'earn',
                'spend_step' => 1000,
                'points_per_step' => 2,
                'bonus_points' => 0,
            ],
            'faster_earn' => [
                'intention' => self::INTENTION_EARN,
                'type' => 'earn',
                'spend_step' => 1000,
                'points_per_step' => 5,
                'bonus_points' => 0,
            ],
            'product_push' => [
                'intention' => self::INTENTION_PRODUCT,
                'type' => 'product_push',
                'spend_step' => 1000,
                'points_per_step' => 2,
                'bonus_points' => 10,
            ],
            'visit_streak' => [
                'intention' => self::INTENTION_RETENTION,
                'type' => 'streak',
                'spend_step' => null,
                'points_per_step' => null,
                'bonus_points' => 30,
            ],
            'birthday_treat' => [
                'intention' => self::INTENTION_RETENTION,
                'type' => 'birthday',
                'spend_step' => null,
                'points_per_step' => null,
                'bonus_points' => 50,
            ],
            'welcome_bonus' => [
                'intention' => self::INTENTION_RETENTION,
                'type' => 'welcome',
                'spend_step' => null,
                'points_per_step' => null,
                'bonus_points' => 20,
            ],
        ];
    }

    public static function localized(string $key): ?array
    {
        $template = self::all()[$key] ?? null;
        if (! $template) {
            return null;
        }

        $template['key'] = $key;
        $template['name'] = __('loop.templates.'.$key.'.name');
        $template['description'] = __('loop.templates.'.$key.'.description');

        return $template;
    }

    /**
     * @return array<string, array{label: string, templates: array<string, array<string, mixed>>}>
     */
    public static function grouped(?array $excludeKeys = null): array
    {
        $excludeKeys = $excludeKeys ?? [];
        $groups = [
            self::INTENTION_EARN => [],
            self::INTENTION_PRODUCT => [],
            self::INTENTION_RETENTION => [],
        ];

        foreach (self::all() as $key => $template) {
            if (in_array($key, $excludeKeys, true)) {
                continue;
            }
            $groups[$template['intention']][$key] = self::localized($key);
        }

        $labels = [
            self::INTENTION_EARN => __('loop.intention_earn'),
            self::INTENTION_PRODUCT => __('loop.intention_product'),
            self::INTENTION_RETENTION => __('loop.intention_retention'),
        ];

        $result = [];
        foreach ($groups as $intention => $templates) {
            if ($templates === []) {
                continue;
            }
            $result[$intention] = [
                'label' => $labels[$intention],
                'templates' => $templates,
            ];
        }

        return $result;
    }

    public static function nameFor(?string $templateKey, string $fallback): string
    {
        if (in_array($templateKey, ['hundred_point_discount', 'earn_with_discount'], true)) {
            return __('loop.templates.everyday_earn.name');
        }

        if (! $templateKey || ! isset(self::all()[$templateKey])) {
            return $fallback;
        }

        return __('loop.templates.'.$templateKey.'.name');
    }

    public static function descriptionFor(?string $templateKey, ?string $fallback = null): ?string
    {
        if (in_array($templateKey, ['hundred_point_discount', 'earn_with_discount'], true)) {
            return __('loop.templates.everyday_earn.description');
        }

        if (! $templateKey || ! isset(self::all()[$templateKey])) {
            return $fallback;
        }

        return __('loop.templates.'.$templateKey.'.description');
    }
}
