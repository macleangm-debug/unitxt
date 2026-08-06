<nav x-data="{ open: false }" class="border-b border-ink/5 bg-white/80 backdrop-blur-md">
    <div class="loop-shell">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <x-loop-logo class="h-9 w-9" />
                    <span class="font-display text-xl font-semibold tracking-tight text-ink">Loop</span>
                </a>
                <div class="hidden sm:flex sm:items-center sm:gap-1">
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">{{ __('loop.admin') }}</x-nav-link>
                        <x-nav-link :href="route('admin.businesses.index')" :active="request()->routeIs('admin.businesses.*')">{{ __('loop.admin_businesses') }}</x-nav-link>
                        <x-nav-link :href="route('admin.referrals.index')" :active="request()->routeIs('admin.referrals.*')">{{ __('loop.admin_referrals') }}</x-nav-link>
                        <x-nav-link :href="route('admin.plans.index')" :active="request()->routeIs('admin.plans.*')">{{ __('loop.admin_plans') }}</x-nav-link>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('loop.home') }}</x-nav-link>
                        @if (Auth::user()->isStaff())
                            <x-nav-link :href="route('till.index')" :active="request()->routeIs('till.*')">{{ __('loop.sale') }}</x-nav-link>
                            @if (Auth::user()->isOwner())
                                <x-nav-link :href="route('settings')" :active="request()->routeIs('settings*') || request()->routeIs('shops.*') || request()->routeIs('campaigns.*') || request()->routeIs('rewards.*') || request()->routeIs('staff.*')">{{ __('loop.settings') }}</x-nav-link>
                            @endif
                        @else
                            <x-nav-link :href="route('memberships.index')" :active="request()->routeIs('memberships.*')">{{ __('loop.wallets') }}</x-nav-link>
                            <x-nav-link :href="route('discover')" :active="request()->routeIs('discover*')">{{ __('loop.discover') }}</x-nav-link>
                        @endif
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="hidden rounded-xl border border-ink/10 bg-white p-0.5 text-xs font-semibold sm:flex">
                    <a href="{{ route('locale', 'en') }}" class="rounded-lg px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-ink text-white' : 'text-ink-muted' }}">EN</a>
                    <a href="{{ route('locale', 'sw') }}" class="rounded-lg px-2 py-1 {{ app()->getLocale() === 'sw' ? 'bg-ink text-white' : 'text-ink-muted' }}">SW</a>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button class="rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium text-ink-muted hover:text-ink">
                        {{ Auth::user()->name }} · {{ __('loop.log_out') }}
                    </button>
                </form>
                <button @click="open = ! open" class="sm:hidden rounded-lg p-2 text-ink-muted">☰</button>
            </div>
        </div>
    </div>
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-ink/5 px-4 py-3 sm:hidden">
        @if (Auth::user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="block py-2 text-sm">{{ __('loop.admin') }}</a>
            <a href="{{ route('admin.businesses.index') }}" class="block py-2 text-sm">{{ __('loop.admin_businesses') }}</a>
            <a href="{{ route('admin.referrals.index') }}" class="block py-2 text-sm">{{ __('loop.admin_referrals') }}</a>
        @else
            <a href="{{ route('dashboard') }}" class="block py-2 text-sm">{{ __('loop.home') }}</a>
            @if (Auth::user()->isStaff())
                <a href="{{ route('till.index') }}" class="block py-2 text-sm">{{ __('loop.sale') }}</a>
                @if (Auth::user()->isOwner())
                    <a href="{{ route('settings') }}" class="block py-2 text-sm">{{ __('loop.settings') }}</a>
                @endif
            @else
                <a href="{{ route('memberships.index') }}" class="block py-2 text-sm">{{ __('loop.wallets') }}</a>
                <a href="{{ route('discover') }}" class="block py-2 text-sm">{{ __('loop.discover') }}</a>
            @endif
        @endif
        <div class="flex gap-2 py-2">
            <a href="{{ route('locale', 'en') }}" class="text-sm font-semibold">EN</a>
            <a href="{{ route('locale', 'sw') }}" class="text-sm font-semibold">SW</a>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-sm text-ink-muted">{{ __('loop.log_out') }}</button></form>
    </div>
</nav>
