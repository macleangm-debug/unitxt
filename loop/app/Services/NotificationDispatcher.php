<?php

namespace App\Services;

use App\Models\InAppNotification;
use App\Models\User;
use App\Support\NotificationSettings;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * Creates in-app notifications while respecting admin channel toggles
 * and the recipient's preferred language.
 */
class NotificationDispatcher
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function notifyInApp(
        User $user,
        string $role,
        string $type,
        string $titleKey,
        string $bodyKey,
        array $params = [],
        ?string $ctaKey = null,
        ?string $url = null,
        string $tone = 'mint',
        ?int $businessId = null,
        ?string $dedupeKey = null,
    ): ?InAppNotification {
        if (! NotificationSettings::enabled($role, 'in_app')) {
            return null;
        }

        if ($dedupeKey) {
            $exists = InAppNotification::query()
                ->where('user_id', $user->id)
                ->where('type', $type)
                ->where('dedupe_key', $dedupeKey)
                ->whereDate('created_at', now()->toDateString())
                ->exists();
            if ($exists) {
                return null;
            }
        }

        $locale = $user->locale
            ?: (in_array(session('locale'), ['en', 'sw'], true) ? session('locale') : null)
            ?: 'en';

        $previous = App::getLocale();
        App::setLocale(in_array($locale, ['en', 'sw'], true) ? $locale : 'en');

        try {
            // Resolve once so stored keys still render correctly later via __().
            $notification = InAppNotification::create([
                'user_id' => $user->id,
                'business_id' => $businessId,
                'audience' => $role === 'owner' || $role === 'business' ? 'owner' : ($role === 'affiliate' ? 'affiliate' : 'customer'),
                'type' => $type,
                'dedupe_key' => $dedupeKey,
                'title_key' => $titleKey,
                'body_key' => $bodyKey,
                'params' => $params,
                'cta_key' => $ctaKey,
                'url' => $url,
                'tone' => $tone,
                'for_date' => now()->toDateString(),
            ]);
        } finally {
            App::setLocale($previous);
        }

        if (NotificationSettings::enabled($role, 'sms')) {
            Log::info('notification.sms_queued', [
                'user_id' => $user->id,
                'type' => $type,
                'locale' => $locale,
            ]);
        }

        if (NotificationSettings::enabled($role, 'email')) {
            Log::info('notification.email_queued', [
                'user_id' => $user->id,
                'type' => $type,
                'locale' => $locale,
            ]);
        }

        return $notification;
    }
}
