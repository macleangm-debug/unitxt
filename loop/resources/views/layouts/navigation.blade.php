<nav x-data="{ open: false }" class="border-b border-ink/5 bg-white/70 backdrop-blur-md">
    <div class="loop-shell">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <x-loop-logo class="h-9 w-9" />
                    <span class="font-display text-xl font-semibold tracking-tight text-ink">Loop</span>
                </a>
                <div class="hidden sm:flex sm:items-center sm:gap-1">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Home</x-nav-link>
                    @if (Auth::user()->isStaff())
                        <x-nav-link :href="route('till.index')" :active="request()->routeIs('till.*')">Till</x-nav-link>
                        @if (Auth::user()->isOwner())
                            <x-nav-link :href="route('shops.index')" :active="request()->routeIs('shops.*')">Shops</x-nav-link>
                            <x-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')">Campaigns</x-nav-link>
                            <x-nav-link :href="route('rewards.index')" :active="request()->routeIs('rewards.*')">Rewards</x-nav-link>
                            <x-nav-link :href="route('staff.index')" :active="request()->routeIs('staff.*')">Staff</x-nav-link>
                        @endif
                    @else
                        <x-nav-link :href="route('memberships.index')" :active="request()->routeIs('memberships.*')">Wallets</x-nav-link>
                        <x-nav-link :href="route('discover')" :active="request()->routeIs('discover*')">Discover</x-nav-link>
                    @endif
                </div>
            </div>
            <div class="hidden sm:flex sm:items-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium text-ink-muted hover:text-ink">
                        {{ Auth::user()->name }} · Log out
                    </button>
                </form>
            </div>
            <button @click="open = ! open" class="sm:hidden rounded-lg p-2 text-ink-muted">☰</button>
        </div>
    </div>
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-ink/5 px-4 py-3 sm:hidden">
        <a href="{{ route('dashboard') }}" class="block py-2 text-sm">Home</a>
        @if (Auth::user()->isStaff())
            <a href="{{ route('till.index') }}" class="block py-2 text-sm">Till</a>
            @if (Auth::user()->isOwner())
                <a href="{{ route('campaigns.index') }}" class="block py-2 text-sm">Campaigns</a>
                <a href="{{ route('staff.index') }}" class="block py-2 text-sm">Staff</a>
            @endif
        @endif
        <form method="POST" action="{{ route('logout') }}" class="pt-2">@csrf<button class="text-sm text-ink-muted">Log out</button></form>
    </div>
</nav>
