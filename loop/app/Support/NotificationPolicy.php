<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class NotificationPolicy
{
    public const AUDIENCE_OWNER = 'owner';

    public const AUDIENCE_CUSTOMER = 'customer';

    public const AUDIENCE_AFFILIATE = 'affiliate';

    public static function inAppEnabled(string $audience): bool
    {
        $settings = NotificationSettings::settings();

        return match ($audience) {
            self::AUDIENCE_OWNER => (bool) $settings['owner_in_app'],
            self::AUDIENCE_CUSTOMER => (bool) $settings['customer_in_app'],
            self::AUDIENCE_AFFILIATE => (bool) $settings['affiliate_in_app'],
            default => false,
        };
    }

    public static function smsEnabled(string $audience): bool
    {
        if (! FeatureFlags::enabled('sms_messaging')) {
            return false;
        }

        $settings = NotificationSettings::settings();

        return match ($audience) {
            self::AUDIENCE_OWNER => (bool) $settings['owner_sms'],
            self::AUDIENCE_CUSTOMER => (bool) $settings['customer_sms'],
            self::AUDIENCE_AFFILIATE => (bool) $settings['affiliate_sms'],
            default => false,
        };
    }

    public static function inQuietHours(?Carbon $at = null): bool
    {
        $settings = NotificationSettings::settings();
        $start = (int) $settings['quiet_hours_start'];
        $end = (int) $settings['quiet_hours_end'];
        $hour = ($at ?? now())->hour;

        if ($start === $end) {
            return false;
        }

        if ($start < $end) {
            return $hour >= $start && $hour < $end;
        }

        return $hour >= $start || $hour < $end;
    }

    /**
     * Platform SMS for a Loop event (raffle, digest). Owner-paid broadcasts
     * still go through MessagingService + this gate for customer SMS.
     */
    public static function allowSmsNow(string $audience, ?Carbon $at = null): bool
    {
        return self::smsEnabled($audience) && ! self::inQuietHours($at);
    }
}
