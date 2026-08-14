@php
    $here = url()->full();
    $user = Auth::user();
@endphp

<nav
    class="sticky top-0 z-40 border-b border-ink/8 bg-white/95 backdrop-blur-md"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', open)"
>
    <div class="loop-shell">
        <div class="flex h-14 items-center gap-3 sm:h-16 sm:gap-4">
            <a
                href="{{ route('dashboard') }}"
                class="flex shrink-0 items-center gap-2"
                @click="$store.loopNav.go(@js(route('dashboard')), $event, { kind: 'tab' })"
            >
                <x-loop-logo class="h-8 w-8 sm:h-9 sm:w-9" />
                <span class="font-display text-lg font-semibold tracking-tight text-ink sm:text-xl">Loop</span>
            </a>

            @if ($user->isCustomer())
                <div class="hidden min-w-0 flex-1 items-center gap-1 md:flex">
                    <x-loop-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" kind="tab">{{ __('loop.home') }}</x-loop-nav-link>
                    <x-loop-nav-link :href="route('memberships.index')" :active="request()->routeIs('memberships.*')" kind="tab">{{ __('loop.wallets') }}</x-loop-nav-link>
                    <x-loop-nav-link :href="route('discover')" :active="request()->routeIs('discover*')" kind="tab">{{ __('loop.discover') }}</x-loop-nav-link>
                </div>
            @else
                <div class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto md:flex">
                    @if ($user->isAdmin())
                        <x-loop-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" kind="tab">{{ __('loop.admin') }}</x-loop-nav-link>
                        <x-loop-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')" kind="tab">{{ __('loop.admin_reports') }}</x-loop-nav-link>
                        <x-loop-nav-link :href="route('admin.businesses.index')" :active="request()->routeIs('admin.businesses.*')" kind="tab">{{ __('loop.admin_businesses') }}</x-loop-nav-link>
                        <x-loop-nav-link :href="route('admin.affiliates.index')" :active="request()->routeIs('admin.affiliates.*')" kind="tab">{{ __('loop.admin_affiliates') }}</x-loop-nav-link>
                        <x-loop-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings*') || request()->routeIs('admin.referrals.*') || request()->routeIs('admin.plans.*')" kind="tab">{{ __('loop.admin_settings_hub') }}</x-loop-nav-link>
                    @elseif ($user->isAffiliate())
                        <x-loop-nav-link :href="route('affiliate.dashboard')" :active="request()->routeIs('affiliate.dashboard') || request()->routeIs('affiliate.setup')" kind="tab">{{ __('loop.home') }}</x-loop-nav-link>
                        <x-nav-link :href="route('affiliate.dashboard').'#share'" :active="false">{{ __('loop.affiliate_nav_share') }}</x-nav-link>
                        <x-nav-link :href="route('affiliate.dashboard').'#referrals'" :active="false">{{ __('loop.affiliate_nav_referrals') }}</x-nav-link>
                        <x-loop-nav-link :href="route('affiliate.payout')" :active="request()->routeIs('affiliate.payout')" kind="tab">{{ __('loop.payout_settings') }}</x-loop-nav-link>
                        <x-loop-nav-link :href="route('affiliates.landing')" :active="request()->routeIs('affiliates.landing')" kind="push">{{ __('loop.affiliate_nav_how') }}</x-loop-nav-link>
                    @else
                        <x-loop-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" kind="tab">{{ __('loop.home') }}</x-loop-nav-link>
                        @if ($user->isStaff())
                            <x-loop-nav-link :href="route('till.index')" :active="request()->routeIs('till.*')" kind="tab">{{ __('loop.sale') }}</x-loop-nav-link>
                            @if ($user->isOwner() || \App\Support\SalesVisibility::frontDeskCanSee())
                                <x-loop-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')" kind="tab">{{ __('loop.transactions') }}</x-loop-nav-link>
                            @endif
                            @if ($user->isOwner())
                                <x-loop-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')" kind="tab">{{ __('loop.customers') }}</x-loop-nav-link>
                                <x-loop-nav-link :href="route('settings')" :active="request()->routeIs('settings*') || request()->routeIs('shops.*') || request()->routeIs('campaigns.*') || request()->routeIs('rewards.*') || request()->routeIs('staff.*') || request()->routeIs('business.*') || request()->routeIs('billing.*') || request()->routeIs('raffles.*') || request()->routeIs('content-studio.*')" kind="tab">{{ __('loop.settings') }}</x-loop-nav-link>
                            @endif
                        @endif
                    @endif
                </div>
            @endif

            <div class="ml-auto flex shrink-0 items-center gap-2">
                @if ($user->isAffiliate())
                    <span class="hidden rounded-lg bg-violet-soft px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-violet sm:inline-flex">{{ __('loop.affiliate_role_badge') }}</span>
                @endif

                @php
                    $unreadNotifications = \App\Models\InAppNotification::query()
                        ->where('user_id', $user->id)
                        ->whereNull('read_at')
                        ->count();
                    $navNotifications = \App\Models\InAppNotification::query()
                        ->where('user_id', $user->id)
                        ->latest()
                        ->limit(5)
                        ->get();
                @endphp
                <div
                    class="relative"
                    x-data="{ open: false }"
                    @keydown.escape.window="open = false"
                >
                    <button
                        type="button"
                        class="relative inline-grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-ink/10 bg-white text-ink hover:border-ink/20"
                        title="{{ __('loop.notifications') }}"
                        @click="open = ! open"
                        :aria-expanded="open.toString()"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                        </svg>
                        @if ($unreadNotifications > 0)
                            <span class="absolute -right-1 -top-1 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-coral px-1 text-[10px] font-bold text-white">{{ min(9, $unreadNotifications) }}{{ $unreadNotifications > 9 ? '+' : '' }}</span>
                        @endif
                    </button>

                    <template x-teleport="body">
                        <div x-show="open" x-cloak class="fixed inset-0 z-[85]" @click="open = false">
                            <div class="absolute inset-0 bg-ink/30 sm:bg-transparent"></div>
                            <div
                                class="absolute inset-x-0 bottom-0 max-h-[75vh] overflow-hidden rounded-t-[1.75rem] bg-white shadow-2xl sm:inset-x-auto sm:bottom-auto sm:right-4 sm:top-16 sm:w-[22rem] sm:rounded-[1.5rem] sm:border sm:border-ink/10"
                                @click.stop
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="translate-y-full sm:translate-y-0 sm:opacity-0 sm:scale-95"
                                x-transition:enter-end="translate-y-0 sm:opacity-100 sm:scale-100"
                            >
                                <div class="mx-auto mt-3 h-1.5 w-12 rounded-full bg-ink/15 sm:hidden"></div>
                                <div class="flex items-center justify-between border-b border-ink/5 px-4 py-3">
                                    <p class="font-display text-base font-semibold">{{ __('loop.notifications') }}</p>
                                    <a href="{{ route('notifications.index') }}" class="text-xs font-semibold text-violet" @click="$store.loopNav.go(@js(route('notifications.index')), $event, { kind: 'push' })">{{ __('loop.view_all') }}</a>
                                </div>
                                <div class="max-h-[60vh] overflow-y-auto">
                                    @forelse ($navNotifications as $n)
                                        <a href="{{ route('notifications.index') }}" class="block border-b border-ink/5 px-4 py-3 last:border-b-0 hover:bg-chalk/70" @click="$store.loopNav.go(@js(route('notifications.index')), $event, { kind: 'push' })">
                                            <div class="flex items-start gap-3">
                                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $n->isUnread() ? 'bg-violet' : 'bg-ink/15' }}"></span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <p class="truncate text-sm font-semibold text-ink">{{ $n->title() }}</p>
                                                        <time class="shrink-0 text-[10px] text-ink-muted">{{ $n->created_at?->format('H:i') }}</time>
                                                    </div>
                                                    <p class="mt-0.5 line-clamp-2 text-xs text-ink-muted">{{ $n->body() }}</p>
                                                </div>
                                            </div>
                                        </a>
                                    @empty
                                        <p class="px-4 py-8 text-center text-sm text-ink-muted">{{ __('loop.no_notifications') }}</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex rounded-xl border border-ink/10 bg-white p-0.5 text-xs font-semibold shadow-sm">
                    <a href="{{ route('locale', ['locale' => 'en', 'return' => $here]) }}" class="rounded-lg px-2.5 py-1.5 {{ app()->getLocale() === 'en' ? 'bg-ink text-white' : 'text-ink-muted' }}">EN</a>
                    <a href="{{ route('locale', ['locale' => 'sw', 'return' => $here]) }}" class="rounded-lg px-2.5 py-1.5 {{ app()->getLocale() === 'sw' ? 'bg-ink text-white' : 'text-ink-muted' }}">SW</a>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                    @csrf
                    <button class="rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium text-ink-muted hover:text-ink">
                        {{ $user->isCustomer() ? __('loop.log_out') : ($user->name.' · '.__('loop.log_out')) }}
                    </button>
                </form>

                <button
                    type="button"
                    @click="open = ! open"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-ink/10 bg-white text-ink md:hidden"
                    :aria-expanded="open.toString()"
                    aria-label="{{ __('loop.menu') }}"
                >
                    <svg x-show="!open" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                    <svg x-show="open" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div>
            <div
                x-show="open"
                x-cloak
                x-transition.opacity.duration.200ms
                class="fixed inset-0 z-[60] bg-ink/45 md:hidden"
                @click="open = false"
                aria-hidden="true"
            ></div>
            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="fixed inset-y-0 right-0 z-[70] flex w-[min(100%,20rem)] flex-col bg-white shadow-2xl md:hidden"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('loop.menu') }}"
            >
            <div class="flex items-center justify-between border-b border-ink/10 px-4 py-4">
                <div>
                    <p class="font-display text-lg font-semibold">{{ __('loop.menu') }}</p>
                    @if ($user->isAffiliate())
                        <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.affiliate_role_badge') }}</p>
                    @elseif ($user->isCustomer())
                        <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.member') }}</p>
                    @else
                        <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-violet">Loop</p>
                    @endif
                </div>
                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-ink text-white" @click="open = false" aria-label="{{ __('loop.close') }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                </button>
            </div>
            <nav class="loop-mobile-actions flex flex-1 flex-col gap-2 overflow-y-auto p-4">
                @if ($user->isAffiliate())
                    <div class="loop-menu-role">
                        <span class="loop-menu-role__mark">AF</span>
                        <div>
                            <p class="loop-menu-role__label">{{ __('loop.your_role') }}</p>
                            <p class="loop-menu-role__title">{{ __('loop.affiliate_role_badge') }}</p>
                        </div>
                    </div>
                @endif

                @if ($user->isCustomer())
                    <x-loop-link :href="route('dashboard')" kind="tab" :current="request()->routeIs('dashboard')">{{ __('loop.home') }}</x-loop-link>
                    <x-loop-link :href="route('memberships.index')" kind="tab" :current="request()->routeIs('memberships.*')">{{ __('loop.wallets') }}</x-loop-link>
                    <x-loop-link :href="route('discover')" kind="tab" :current="request()->routeIs('discover*')">{{ __('loop.discover') }}</x-loop-link>
                @elseif ($user->isAdmin())
                    <x-loop-link :href="route('admin.dashboard')" kind="tab" :current="request()->routeIs('admin.dashboard')">{{ __('loop.admin') }}</x-loop-link>
                    <x-loop-link :href="route('admin.reports.index')" kind="tab">{{ __('loop.admin_reports') }}</x-loop-link>
                    <x-loop-link :href="route('admin.businesses.index')" kind="tab">{{ __('loop.admin_businesses') }}</x-loop-link>
                    <x-loop-link :href="route('admin.affiliates.index')" kind="tab">{{ __('loop.admin_affiliates') }}</x-loop-link>
                    <x-loop-link :href="route('admin.settings')" kind="tab">{{ __('loop.admin_settings_hub') }}</x-loop-link>
                @elseif ($user->isAffiliate())
                    <x-loop-link :href="route('affiliate.dashboard')" kind="tab" :current="request()->routeIs('affiliate.dashboard') || request()->routeIs('affiliate.setup')">{{ __('loop.home') }}</x-loop-link>
                    <a href="{{ route('affiliate.dashboard') }}#share">{{ __('loop.affiliate_nav_share') }}</a>
                    <a href="{{ route('affiliate.dashboard') }}#referrals">{{ __('loop.affiliate_nav_referrals') }}</a>
                    <x-loop-link :href="route('affiliate.payout')" kind="tab" :current="request()->routeIs('affiliate.payout')">{{ __('loop.payout_settings') }}</x-loop-link>
                    <x-loop-link :href="route('affiliates.landing')" kind="push">{{ __('loop.affiliate_nav_how') }}</x-loop-link>
                @else
                    <x-loop-link :href="route('dashboard')" kind="tab" :current="request()->routeIs('dashboard')">{{ __('loop.home') }}</x-loop-link>
                    @if ($user->isStaff())
                        <x-loop-link :href="route('till.index')" kind="tab" :current="request()->routeIs('till.*')">{{ __('loop.sale') }}</x-loop-link>
                        @if ($user->isOwner() || \App\Support\SalesVisibility::frontDeskCanSee())
                            <x-loop-link :href="route('transactions.index')" kind="tab">{{ __('loop.transactions') }}</x-loop-link>
                        @endif
                        @if ($user->isOwner())
                            <x-loop-link :href="route('customers.index')" kind="tab">{{ __('loop.customers') }}</x-loop-link>
                            <x-loop-link :href="route('settings')" kind="tab">{{ __('loop.settings') }}</x-loop-link>
                        @endif
                    @endif
                @endif
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="loop-btn-ghost w-full">{{ __('loop.log_out') }}</button>
                </form>
            </nav>
            </div>
        </div>
    </template>
</nav>

@if ($user->isCustomer())
    @php
        $navIndex = request()->routeIs('dashboard') ? 0 : (request()->routeIs('memberships.*') ? 1 : (request()->routeIs('discover*') ? 2 : 0));
    @endphp
    <nav class="loop-bottom-nav md:hidden" aria-label="{{ __('loop.member') }}">
        <div class="relative mx-auto grid max-w-lg grid-cols-3 px-2 py-1.5 text-center text-[11px] font-semibold">
            <div class="loop-nav-pill" style="left: calc({{ $navIndex }} * 33.333% + 0.25rem)"></div>
            <a href="{{ route('dashboard') }}" @click="$store.loopNav.go(@js(route('dashboard')), $event, { kind: 'tab' })" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 0 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">⌂</span>
                {{ __('loop.home') }}
            </a>
            <a href="{{ route('memberships.index') }}" @click="$store.loopNav.go(@js(route('memberships.index')), $event, { kind: 'tab' })" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 1 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">◇</span>
                {{ __('loop.wallets') }}
            </a>
            <a href="{{ route('discover') }}" @click="$store.loopNav.go(@js(route('discover')), $event, { kind: 'tab' })" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 2 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">◎</span>
                {{ __('loop.browse_campaigns') }}
            </a>
        </div>
    </nav>
@endif
