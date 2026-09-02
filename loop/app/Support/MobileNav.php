<?php

namespace App\Support;

use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class MobileNav
{
    public static function board(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->isOwner()) {
            return 'owner';
        }

        if ($user->isFrontDesk()) {
            return 'front_desk';
        }

        if ($user->isCustomer()) {
            return 'member';
        }

        if ($user->isAffiliate()) {
            return 'affiliate';
        }

        return null;
    }

    public static function enabled(?User $user): bool
    {
        return self::board($user) !== null;
    }

    /**
     * @return list<array{href: string, label: string, icon: string, active: bool, badge?: int}>
     */
    public static function tabs(User $user): array
    {
        $unread = self::unreadCount($user);

        return match (self::board($user)) {
            'owner' => [
                self::tab('dashboard', 'home', __('loop.home'), $user->isOwner() && request()->routeIs('dashboard')),
                self::tab('customers.index', 'customers', __('loop.nav_members'), request()->routeIs('customers.*')),
                self::tab('till.index', 'sale', __('loop.nav_till'), request()->routeIs('till.*')),
                self::tab('campaigns.index', 'campaigns', __('loop.nav_campaigns'), request()->routeIs('campaigns.*') || request()->routeIs('rewards.*')),
                self::tab('more.index', 'more', __('loop.nav_more'), self::ownerMoreActive(), $unread),
            ],
            'front_desk' => array_values(array_filter([
                self::tab('till.index', 'sale', __('loop.sale'), request()->routeIs('till.*')),
                SalesVisibility::frontDeskCanSee()
                    ? self::tab('transactions.index', 'activity', __('loop.activity'), request()->routeIs('transactions.*'))
                    : null,
                self::tab('more.index', 'more', __('loop.nav_more'), request()->routeIs('more.*', 'notifications.*'), $unread),
            ])),
            'member' => [
                self::tab('dashboard', 'home', __('loop.home'), request()->routeIs('dashboard')),
                self::tab('discover', 'discover', __('loop.discover'), request()->routeIs('discover*')),
                self::tab('memberships.index', 'rewards', __('loop.nav_rewards'), request()->routeIs('memberships.*')),
                self::tab('member.activity', 'activity', __('loop.activity'), request()->routeIs('member.activity')),
                self::tab('more.index', 'account', __('loop.nav_account'), request()->routeIs('more.*', 'notifications.*', 'stories.*', 'help', 'legal.*'), $unread),
            ],
            'affiliate' => [
                self::tab('affiliate.dashboard', 'home', __('loop.home'), request()->routeIs('affiliate.dashboard') || request()->routeIs('affiliate.setup')),
                [
                    'href' => route('affiliate.dashboard').'#referrals',
                    'label' => __('loop.nav_referrals'),
                    'icon' => 'referrals',
                    'active' => false,
                    'badge' => 0,
                ],
                self::tab('affiliate.withdraw', 'earnings', __('loop.nav_earnings'), request()->routeIs('affiliate.withdraw')),
                self::tab('more.index', 'more', __('loop.nav_more'), request()->routeIs('more.*', 'affiliate.payout', 'notifications.*'), $unread),
            ],
            default => [],
        };
    }

    /**
     * @return list<array{title: string, items: list<array{href: string, label: string, icon: string}>}>
     */
    public static function moreGroups(User $user): array
    {
        return match (self::board($user)) {
            'owner' => self::ownerMore($user),
            'front_desk' => self::frontDeskMore($user),
            'member' => self::memberMore(),
            'affiliate' => self::affiliateMore(),
            default => [],
        };
    }

    /**
     * @return list<array{title: string, items: list<array{href: string, label: string, icon: string}>}>
     */
    private static function ownerMore(User $user): array
    {
        $business = $user->ownedBusiness;
        $grow = [
            self::item('rewards.index', 'rewards', __('loop.offers')),
        ];
        if ($business && GameSettings::engineOn()) {
            $grow[] = self::item('games.index', 'games', __('loop.games_wins'));
        }
        if (FeatureFlags::enabled('raffles')) {
            $grow[] = self::item('raffles.index', 'raffles', __('loop.raffles'));
        }
        if (FeatureFlags::enabled('content_studio')) {
            $grow[] = self::item('content-studio.index', 'studio', __('loop.content_studio'));
        }

        $run = [
            self::item('staff.index', 'staff', __('loop.staff')),
            self::item('shops.index', 'shops', __('loop.shops')),
            self::item('transactions.index', 'activity', __('loop.transactions')),
        ];
        if (FeatureFlags::enabled('sms_messaging')) {
            $run[] = self::item('members.messages.index', 'messages', __('loop.member_messages'));
        }

        $loop = [
            self::item('billing.show', 'billing', __('loop.billing')),
            self::item('settings.referrals', 'referrals', __('loop.referrals')),
            self::item('business.edit', 'settings', __('loop.business')),
            self::item('settings', 'more', __('loop.settings')),
            self::item('notifications.index', 'notifications', __('loop.notifications')),
            self::item('help', 'help', __('loop.help_faqs')),
            self::item('legal.account', 'legal', __('loop.legal_documents')),
        ];

        return [
            ['title' => __('loop.more_grow'), 'items' => $grow],
            ['title' => __('loop.more_run'), 'items' => $run],
            ['title' => __('loop.more_loop'), 'items' => $loop],
        ];
    }

    /**
     * @return list<array{title: string, items: list<array{href: string, label: string, icon: string}>}>
     */
    private static function frontDeskMore(User $user): array
    {
        $items = [
            self::item('till.index', 'sale', __('loop.sale')),
            self::item('notifications.index', 'notifications', __('loop.notifications')),
            self::item('help', 'help', __('loop.help_faqs')),
            self::item('legal.account', 'legal', __('loop.legal_documents')),
        ];

        return [
            ['title' => __('loop.more_account'), 'items' => $items],
        ];
    }

    /**
     * @return list<array{title: string, items: list<array{href: string, label: string, icon: string}>}>
     */
    private static function memberMore(): array
    {
        return [
            [
                'title' => __('loop.more_account'),
                'items' => [
                    self::item('notifications.index', 'notifications', __('loop.notifications')),
                    self::item('stories.index', 'stories', __('loop.stories')),
                    self::item('help', 'help', __('loop.help_faqs')),
                    self::item('legal.account', 'legal', __('loop.legal_documents')),
                ],
            ],
        ];
    }

    /**
     * @return list<array{title: string, items: list<array{href: string, label: string, icon: string}>}>
     */
    private static function affiliateMore(): array
    {
        return [
            [
                'title' => __('loop.more_account'),
                'items' => [
                    [
                        'href' => route('affiliate.dashboard').'#share',
                        'label' => __('loop.nav_share'),
                        'icon' => 'share',
                    ],
                    self::item('affiliate.payout', 'earnings', __('loop.payout_settings')),
                    self::item('notifications.index', 'notifications', __('loop.notifications')),
                    self::item('help', 'help', __('loop.help_faqs')),
                    self::item('legal.account', 'legal', __('loop.legal_documents')),
                ],
            ],
        ];
    }

    /**
     * @return array{href: string, label: string, icon: string, active: bool, badge: int}
     */
    private static function tab(string $route, string $icon, string $label, bool $active, int $badge = 0): array
    {
        return [
            'href' => Route::has($route) ? route($route) : '#',
            'label' => $label,
            'icon' => $icon,
            'active' => $active,
            'badge' => $badge,
        ];
    }

    /**
     * @return array{href: string, label: string, icon: string}
     */
    private static function item(string $route, string $icon, string $label): array
    {
        return [
            'href' => Route::has($route) ? route($route) : '#',
            'label' => $label,
            'icon' => $icon,
        ];
    }

    private static function ownerMoreActive(): bool
    {
        return request()->routeIs(
            'more.*',
            'settings*',
            'shops.*',
            'staff.*',
            'business.*',
            'billing.*',
            'raffles.*',
            'games.*',
            'content-studio.*',
            'members.messages.*',
            'notifications.*',
            'transactions.*',
        );
    }

    private static function unreadCount(User $user): int
    {
        return (int) InAppNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
