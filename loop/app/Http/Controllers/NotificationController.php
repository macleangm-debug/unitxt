<?php

namespace App\Http\Controllers;

use App\Models\InAppNotification;
use App\Services\DailyNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, DailyNotificationService $daily): View
    {
        $user = $request->user();
        abort_unless($user->isOwner() || $user->isAdmin() || $user->isAffiliate() || $user->isCustomer(), 403);

        if ($user->isOwner()) {
            $daily->ensureTodayForOwner($user);
        }

        $notifications = InAppNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $unreadCount = InAppNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        $audience = match (true) {
            $user->isCustomer() => 'member',
            $user->isAffiliate() => 'affiliate',
            $user->isOwner() || $user->isStaff() => 'business',
            default => 'admin',
        };

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'audience' => $audience,
        ]);
    }

    public function markRead(Request $request, InAppNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        if ($notification->url) {
            return redirect()->to($notification->url);
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        InAppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
