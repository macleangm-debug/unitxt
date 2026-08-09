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
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2">
                <x-loop-logo class="h-8 w-8 sm:h-9 sm:w-9" />
                <span class="font-display text-lg font-semibold tracking-tight text-ink sm:text-xl">Loop</span>
            </a>

            @if ($user->isCustomer())
                <div class="hidden min-w-0 flex-1 items-center gap-1 md:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('loop.home') }}</x-nav-link>
                    <x-nav-link :href="route('memberships.index')" :active="request()->routeIs('memberships.*')">{{ __('loop.wallets') }}</x-nav-link>
                    <x-nav-link :href="route('discover')" :active="request()->routeIs('discover*')">{{ __('loop.discover') }}</x-nav-link>
                </div>
            @else
                <div class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto md:flex">
                    @if ($user->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">{{ __('loop.admin') }}</x-nav-link>
                        <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')">{{ __('loop.admin_reports') }}</x-nav-link>
                        <x-nav-link :href="route('admin.businesses.index')" :active="request()->routeIs('admin.businesses.*')">{{ __('loop.admin_businesses') }}</x-nav-link>
                        <x-nav-link :href="route('admin.affiliates.index')" :active="request()->routeIs('admin.affiliates.*')">{{ __('loop.admin_affiliates') }}</x-nav-link>
                        <x-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings*') || request()->routeIs('admin.referrals.*') || request()->routeIs('admin.plans.*')">{{ __('loop.admin_settings_hub') }}</x-nav-link>
                    @elseif ($user->isAffiliate())
                        <x-nav-link :href="route('affiliate.dashboard')" :active="request()->routeIs('affiliate.dashboard') || request()->routeIs('affiliate.setup')">{{ __('loop.home') }}</x-nav-link>
                        <x-nav-link :href="route('affiliate.dashboard').'#share'" :active="false">{{ __('loop.affiliate_nav_share') }}</x-nav-link>
                        <x-nav-link :href="route('affiliate.dashboard').'#referrals'" :active="false">{{ __('loop.affiliate_nav_referrals') }}</x-nav-link>
                        <x-nav-link :href="route('affiliate.payout')" :active="request()->routeIs('affiliate.payout')">{{ __('loop.payout_settings') }}</x-nav-link>
                        <x-nav-link :href="route('affiliates.landing')" :active="request()->routeIs('affiliates.landing')">{{ __('loop.affiliate_nav_how') }}</x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('loop.home') }}</x-nav-link>
                        @if ($user->isStaff())
                            <x-nav-link :href="route('till.index')" :active="request()->routeIs('till.*')">{{ __('loop.sale') }}</x-nav-link>
                            @if ($user->isOwner() || \App\Support\SalesVisibility::frontDeskCanSee())
                                <x-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')">{{ __('loop.transactions') }}</x-nav-link>
                            @endif
                            @if ($user->isOwner())
                                <x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">{{ __('loop.customers') }}</x-nav-link>
                                <x-nav-link :href="route('settings')" :active="request()->routeIs('settings*') || request()->routeIs('shops.*') || request()->routeIs('campaigns.*') || request()->routeIs('rewards.*') || request()->routeIs('staff.*') || request()->routeIs('business.*') || request()->routeIs('billing.*') || request()->routeIs('raffles.*') || request()->routeIs('content-studio.*')">{{ __('loop.settings') }}</x-nav-link>
                            @endif
                        @endif
                    @endif
                </div>
            @endif

            <div class="ml-auto flex shrink-0 items-center gap-2">
                @if ($user->isCustomer())
                    <p class="hidden text-sm font-medium text-ink-muted sm:block">{{ $user->full_phone ?? $user->phone }}</p>
                @endif

                @if ($user->isAffiliate())
                    <span class="hidden rounded-lg bg-violet-soft px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-violet sm:inline-flex">{{ __('loop.affiliate_role_badge') }}</span>
                @endif

                @php
                    $unreadNotifications = \App\Models\InAppNotification::query()
                        ->where('user_id', $user->id)
                        ->whereNull('read_at')
                        ->count();
                @endphp
                <a href="{{ route('notifications.index') }}" class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-ink/10 bg-white text-ink hover:border-ink/20" title="{{ __('loop.notifications') }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                    </svg>
                    @if ($unreadNotifications > 0)
                        <span class="absolute -right-1 -top-1 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-coral px-1 text-[10px] font-bold text-white">{{ min(9, $unreadNotifications) }}{{ $unreadNotifications > 9 ? '+' : '' }}</span>
                    @endif
                </a>

                {{-- Language always visible outside the menu --}}
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
                    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>{{ __('loop.home') }}</a>
                    <a href="{{ route('memberships.index') }}" @if(request()->routeIs('memberships.*')) aria-current="page" @endif>{{ __('loop.wallets') }}</a>
                    <a href="{{ route('discover') }}" @if(request()->routeIs('discover*')) aria-current="page" @endif>{{ __('loop.discover') }}</a>
                @elseif ($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>{{ __('loop.admin') }}</a>
                    <a href="{{ route('admin.reports.index') }}">{{ __('loop.admin_reports') }}</a>
                    <a href="{{ route('admin.businesses.index') }}">{{ __('loop.admin_businesses') }}</a>
                    <a href="{{ route('admin.affiliates.index') }}">{{ __('loop.admin_affiliates') }}</a>
                    <a href="{{ route('admin.settings') }}">{{ __('loop.admin_settings_hub') }}</a>
                @elseif ($user->isAffiliate())
                    <a href="{{ route('affiliate.dashboard') }}" @if(request()->routeIs('affiliate.dashboard') || request()->routeIs('affiliate.setup')) aria-current="page" @endif>{{ __('loop.home') }}</a>
                    <a href="{{ route('affiliate.dashboard') }}#share">{{ __('loop.affiliate_nav_share') }}</a>
                    <a href="{{ route('affiliate.dashboard') }}#referrals">{{ __('loop.affiliate_nav_referrals') }}</a>
                    <a href="{{ route('affiliate.payout') }}" @if(request()->routeIs('affiliate.payout')) aria-current="page" @endif>{{ __('loop.payout_settings') }}</a>
                    <a href="{{ route('affiliates.landing') }}">{{ __('loop.affiliate_nav_how') }}</a>
                @else
                    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>{{ __('loop.home') }}</a>
                    @if ($user->isStaff())
                        <a href="{{ route('till.index') }}" @if(request()->routeIs('till.*')) aria-current="page" @endif>{{ __('loop.sale') }}</a>
                        @if ($user->isOwner() || \App\Support\SalesVisibility::frontDeskCanSee())
                            <a href="{{ route('transactions.index') }}">{{ __('loop.transactions') }}</a>
                        @endif
                        @if ($user->isOwner())
                            <a href="{{ route('customers.index') }}">{{ __('loop.customers') }}</a>
                            <a href="{{ route('settings') }}">{{ __('loop.settings') }}</a>
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
    <nav class="loop-bottom-nav md:hidden" aria-label="{{ __('loop.home') }}">
        <div class="relative mx-auto grid max-w-lg grid-cols-3 px-2 py-1.5 text-center text-[11px] font-semibold">
            <div class="loop-nav-pill" style="left: calc({{ $navIndex }} * 33.333% + 0.25rem)"></div>
            <a href="{{ route('dashboard') }}" @click="$store.loopNav.go(@js(route('dashboard')), $event)" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 0 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">⌂</span>
                {{ __('loop.home') }}
            </a>
            <a href="{{ route('memberships.index') }}" @click="$store.loopNav.go(@js(route('memberships.index')), $event)" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 1 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">◇</span>
                {{ __('loop.wallets') }}
            </a>
            <a href="{{ route('discover') }}" @click="$store.loopNav.go(@js(route('discover')), $event)" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 2 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">◎</span>
                {{ __('loop.browse_campaigns') }}
            </a>
        </div>
    </nav>
@endif
