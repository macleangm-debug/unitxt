<nav
    x-data="{ open: false }"
    @class([
        'border-b border-ink/8 bg-white/90 backdrop-blur-md',
        'sticky top-0 z-30' => Auth::user()?->isCustomer(),
    ])
>
    <div class="loop-shell">
        <div class="flex h-14 items-center gap-3 sm:h-16 sm:gap-4">
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2">
                <x-loop-logo class="h-8 w-8 sm:h-9 sm:w-9" />
                <span class="font-display text-lg font-semibold tracking-tight text-ink sm:text-xl">Loop</span>
            </a>

            @if (Auth::user()->isCustomer())
                <div class="hidden min-w-0 flex-1 items-center gap-1 md:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('loop.home') }}</x-nav-link>
                    <x-nav-link :href="route('memberships.index')" :active="request()->routeIs('memberships.*')">{{ __('loop.wallets') }}</x-nav-link>
                    <x-nav-link :href="route('discover')" :active="request()->routeIs('discover*')">{{ __('loop.discover') }}</x-nav-link>
                </div>
                <div class="flex-1 md:hidden"></div>
                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <p class="hidden text-sm font-medium text-ink-muted sm:block">{{ Auth::user()->full_phone ?? Auth::user()->phone }}</p>
                    <div class="hidden rounded-xl border border-ink/10 bg-white p-0.5 text-xs font-semibold sm:flex">
                        <a href="{{ route('locale', 'en') }}" class="rounded-lg px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-ink text-white' : 'text-ink-muted' }}">EN</a>
                        <a href="{{ route('locale', 'sw') }}" class="rounded-lg px-2 py-1 {{ app()->getLocale() === 'sw' ? 'bg-ink text-white' : 'text-ink-muted' }}">SW</a>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button class="rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium text-ink-muted hover:text-ink">{{ __('loop.log_out') }}</button>
                    </form>
                    <button @click="open = ! open" class="rounded-lg p-2 text-ink-muted sm:hidden" aria-label="Menu">☰</button>
                </div>
            @else
                <div class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto sm:flex">
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">{{ __('loop.admin') }}</x-nav-link>
                        <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')">{{ __('loop.admin_reports') }}</x-nav-link>
                        <x-nav-link :href="route('admin.businesses.index')" :active="request()->routeIs('admin.businesses.*')">{{ __('loop.admin_businesses') }}</x-nav-link>
                        <x-nav-link :href="route('admin.affiliates.index')" :active="request()->routeIs('admin.affiliates.*')">{{ __('loop.admin_affiliates') }}</x-nav-link>
                        <x-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings*') || request()->routeIs('admin.referrals.*') || request()->routeIs('admin.plans.*')">{{ __('loop.admin_settings_hub') }}</x-nav-link>
                    @elseif (Auth::user()->isAffiliate())
                        <x-nav-link :href="route('affiliate.dashboard')" :active="request()->routeIs('affiliate.dashboard') || request()->routeIs('affiliate.setup')">{{ __('loop.home') }}</x-nav-link>
                        <x-nav-link :href="route('affiliate.payout')" :active="request()->routeIs('affiliate.payout')">{{ __('loop.payout_settings') }}</x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('loop.home') }}</x-nav-link>
                        @if (Auth::user()->isStaff())
                            <x-nav-link :href="route('till.index')" :active="request()->routeIs('till.*')">{{ __('loop.sale') }}</x-nav-link>
                            @if (Auth::user()->isOwner() || \App\Support\SalesVisibility::frontDeskCanSee())
                                <x-nav-link :href="route('transactions.index')" :active="request()->routeIs('transactions.*')">{{ __('loop.transactions') }}</x-nav-link>
                            @endif
                            @if (Auth::user()->isOwner())
                                <x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')">{{ __('loop.customers') }}</x-nav-link>
                                <x-nav-link :href="route('settings')" :active="request()->routeIs('settings*') || request()->routeIs('shops.*') || request()->routeIs('campaigns.*') || request()->routeIs('rewards.*') || request()->routeIs('staff.*') || request()->routeIs('business.*') || request()->routeIs('billing.*') || request()->routeIs('raffles.*') || request()->routeIs('content-studio.*')">{{ __('loop.settings') }}</x-nav-link>
                            @endif
                        @endif
                    @endif
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <div class="hidden rounded-xl border border-ink/10 bg-white p-0.5 text-xs font-semibold sm:flex">
                        <a href="{{ route('locale', 'en') }}" class="rounded-lg px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-ink text-white' : 'text-ink-muted' }}">EN</a>
                        <a href="{{ route('locale', 'sw') }}" class="rounded-lg px-2 py-1 {{ app()->getLocale() === 'sw' ? 'bg-ink text-white' : 'text-ink-muted' }}">SW</a>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button class="rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium text-ink-muted hover:text-ink">
                            {{ Auth::user()->name.' · '.__('loop.log_out') }}
                        </button>
                    </form>
                    <button @click="open = ! open" class="rounded-lg p-2 text-ink-muted sm:hidden">☰</button>
                </div>
            @endif
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-ink/8 px-4 py-3 sm:hidden">
        @if (Auth::user()->isCustomer())
            <a href="{{ route('dashboard') }}" class="block py-2 text-sm">{{ __('loop.home') }}</a>
            <a href="{{ route('memberships.index') }}" class="block py-2 text-sm">{{ __('loop.wallets') }}</a>
            <a href="{{ route('discover') }}" class="block py-2 text-sm">{{ __('loop.discover') }}</a>
        @elseif (Auth::user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="block py-2 text-sm">{{ __('loop.admin') }}</a>
            <a href="{{ route('admin.reports.index') }}" class="block py-2 text-sm">{{ __('loop.admin_reports') }}</a>
            <a href="{{ route('admin.businesses.index') }}" class="block py-2 text-sm">{{ __('loop.admin_businesses') }}</a>
            <a href="{{ route('admin.settings') }}" class="block py-2 text-sm">{{ __('loop.admin_settings_hub') }}</a>
        @elseif (Auth::user()->isAffiliate())
            <a href="{{ route('affiliate.dashboard') }}" class="block py-2 text-sm">{{ __('loop.home') }}</a>
            <a href="{{ route('affiliate.payout') }}" class="block py-2 text-sm">{{ __('loop.payout_settings') }}</a>
        @else
            <a href="{{ route('dashboard') }}" class="block py-2 text-sm">{{ __('loop.home') }}</a>
            @if (Auth::user()->isStaff())
                <a href="{{ route('till.index') }}" class="block py-2 text-sm">{{ __('loop.sale') }}</a>
                @if (Auth::user()->isOwner() || \App\Support\SalesVisibility::frontDeskCanSee())
                    <a href="{{ route('transactions.index') }}" class="block py-2 text-sm">{{ __('loop.transactions') }}</a>
                @endif
                @if (Auth::user()->isOwner())
                    <a href="{{ route('customers.index') }}" class="block py-2 text-sm">{{ __('loop.customers') }}</a>
                    <a href="{{ route('settings') }}" class="block py-2 text-sm">{{ __('loop.settings') }}</a>
                @endif
            @endif
        @endif
        <div class="flex gap-2 py-2">
            <a href="{{ route('locale', 'en') }}" class="text-sm font-semibold">EN</a>
            <a href="{{ route('locale', 'sw') }}" class="text-sm font-semibold">SW</a>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-ink-muted">{{ __('loop.log_out') }}</button></form>
    </div>
</nav>

@if (Auth::user()->isCustomer())
    @php
        $navIndex = request()->routeIs('dashboard') ? 0 : (request()->routeIs('memberships.*') ? 1 : (request()->routeIs('discover*') ? 2 : 0));
    @endphp
    <nav class="loop-bottom-nav md:hidden" aria-label="{{ __('loop.home') }}">
        <div class="relative mx-auto grid max-w-lg grid-cols-3 px-2 py-1.5 text-center text-[11px] font-semibold">
            <div class="loop-nav-pill" style="left: calc({{ $navIndex }} * 33.333% + 0.25rem)"></div>
            <a href="{{ route('dashboard') }}" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 0 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">⌂</span>
                {{ __('loop.home') }}
            </a>
            <a href="{{ route('memberships.index') }}" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 1 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">◇</span>
                {{ __('loop.wallets') }}
            </a>
            <a href="{{ route('discover') }}" @class(['relative z-10 flex flex-col items-center gap-1 rounded-xl px-2 py-2 transition-colors duration-200', $navIndex === 2 ? 'text-violet' : 'text-ink-muted'])>
                <span class="text-base leading-none">◎</span>
                {{ __('loop.browse_campaigns') }}
            </a>
        </div>
    </nav>
@endif
