<?php

namespace App\Support;

class LegalCatalog
{
    public const KEY = 'legal_identity';

    /**
     * @return array<string, array{group: string, audience: list<string>, requires_acceptance: bool}>
     */
    public static function types(): array
    {
        return [
            'terms' => ['group' => 'using', 'audience' => ['member', 'business', 'staff', 'affiliate'], 'requires_acceptance' => true],
            'acceptable-use' => ['group' => 'using', 'audience' => ['member', 'business', 'staff', 'affiliate'], 'requires_acceptance' => false],
            'privacy' => ['group' => 'privacy', 'audience' => ['member', 'business', 'staff', 'affiliate'], 'requires_acceptance' => false],
            'data-rights' => ['group' => 'privacy', 'audience' => ['member', 'business', 'staff', 'affiliate'], 'requires_acceptance' => false],
            'cookies' => ['group' => 'privacy', 'audience' => ['member', 'business', 'staff', 'affiliate'], 'requires_acceptance' => false],
            'rewards-terms' => ['group' => 'programmes', 'audience' => ['member', 'business'], 'requires_acceptance' => false],
            'promotional-rules' => ['group' => 'programmes', 'audience' => ['member', 'business'], 'requires_acceptance' => false],
            'business-terms' => ['group' => 'businesses', 'audience' => ['business'], 'requires_acceptance' => true],
            'payment-terms' => ['group' => 'businesses', 'audience' => ['business'], 'requires_acceptance' => false],
            'affiliate-terms' => ['group' => 'affiliates', 'audience' => ['affiliate'], 'requires_acceptance' => true],
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugsForRole(?string $role): array
    {
        $role = match ($role) {
            'owner', 'front_desk' => 'business',
            'customer' => 'member',
            default => $role,
        };

        $slugs = [];
        foreach (self::types() as $slug => $meta) {
            if (in_array('all', $meta['audience'], true) || ($role && in_array($role, $meta['audience'], true))) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * @return array<string, mixed>
     */
    public static function identity(): array
    {
        return array_merge(self::identityDefaults(), \App\Models\PlatformSetting::getValue(self::KEY, []));
    }

    /**
     * @return array<string, string>
     */
    public static function identityDefaults(): array
    {
        return [
            'legal_name' => 'Loop operating company (draft — insert registered name)',
            'registration_number' => '',
            'tin' => '',
            'address' => 'United Republic of Tanzania',
            'legal_email' => 'legal@loop.africa',
            'dpo_email' => 'privacy@loop.africa',
            'support_email' => 'support@loop.africa',
            'support_phone' => '',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function normalizeIdentity(array $input): array
    {
        $out = self::identityDefaults();
        foreach ($out as $key => $default) {
            $out[$key] = trim((string) ($input[$key] ?? $default));
        }

        return $out;
    }
}
