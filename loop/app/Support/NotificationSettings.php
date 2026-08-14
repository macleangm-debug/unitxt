<?php

namespace App\Support;

use App\Models\PlatformSetting;

class NotificationSettings
{
    public const KEY = 'notification_settings';

    /**
     * @return array<string, mixed>
     */
    public static function settings(): array
    {
        $defaults = self::defaults();
        $stored = PlatformSetting::getValue(self::KEY, []);
        if (! is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'owner_in_app' => true,
            'owner_email' => false,
            'owner_sms' => false,
            'customer_in_app' => true,
            'customer_sms' => false,
            'affiliate_in_app' => true,
            'affiliate_sms' => true,
            'admin_digest' => true,
            'holiday_messages' => true,
            'in_app_digest' => true,
            'trial_reminders' => true,
            'quiet_hours_start' => 21,
            'quiet_hours_end' => 7,
        ];
    }

    /**
     * Whether a role can receive a given channel.
     * Roles: owner (business), customer (member), affiliate.
     * Channels: in_app, sms, email.
     */
    public static function enabled(string $role, string $channel): bool
    {
        $settings = self::settings();
        $key = match ($role) {
            'owner', 'business' => match ($channel) {
                'in_app' => 'owner_in_app',
                'sms' => 'owner_sms',
                'email' => 'owner_email',
                default => null,
            },
            'customer', 'member' => match ($channel) {
                'in_app' => 'customer_in_app',
                'sms' => 'customer_sms',
                default => null,
            },
            'affiliate' => match ($channel) {
                'in_app' => 'affiliate_in_app',
                'sms' => 'affiliate_sms',
                default => null,
            },
            default => null,
        };

        if ($key === null) {
            return false;
        }

        if ($channel === 'in_app' && empty($settings['in_app_digest'])) {
            return false;
        }

        return ! empty($settings[$key]);
    }

    public static function withinQuietHours(?\DateTimeInterface $at = null): bool
    {
        $settings = self::settings();
        $hour = (int) ($at?->format('G') ?? now()->format('G'));
        $start = (int) $settings['quiet_hours_start'];
        $end = (int) $settings['quiet_hours_end'];

        if ($start === $end) {
            return false;
        }

        if ($start < $end) {
            return $hour >= $start && $hour < $end;
        }

        return $hour >= $start || $hour < $end;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $input): array
    {
        return [
            'owner_in_app' => ! empty($input['owner_in_app']),
            'owner_email' => ! empty($input['owner_email']),
            'owner_sms' => ! empty($input['owner_sms']),
            'customer_in_app' => ! empty($input['customer_in_app']),
            'customer_sms' => ! empty($input['customer_sms']),
            'affiliate_in_app' => ! empty($input['affiliate_in_app']),
            'affiliate_sms' => ! empty($input['affiliate_sms']),
            'admin_digest' => ! empty($input['admin_digest']),
            'holiday_messages' => ! empty($input['holiday_messages']),
            'in_app_digest' => ! empty($input['in_app_digest']),
            'trial_reminders' => ! empty($input['trial_reminders']),
            'quiet_hours_start' => max(0, min(23, (int) ($input['quiet_hours_start'] ?? 21))),
            'quiet_hours_end' => max(0, min(23, (int) ($input['quiet_hours_end'] ?? 7))),
        ];
    }
}
