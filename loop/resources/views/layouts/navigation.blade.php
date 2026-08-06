<nav x-data="{ open: false }" class="border-b border-ink/5 bg-white/70 backdrop-blur-md">
    <div class="loop-shell">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-ink text-mint font-display text-lg font-bold">L</span>
                    <span class="font-display text-xl font-semibold tracking-tight text-ink">Loop</span>
                </a>

                <div class="hidden sm:flex sm:items-center sm:gap-1">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>

                    @if (Auth::user()->isBusiness())
                        <x-nav-link :href="route('shops.index')" :active="request()->routeIs('shops.*')">
                            Shops
                        </x-nav-link>
                        <x-nav-link :href="route('campaigns.index')" :active="request()->routeIs('campaigns.*')">
                            Campaigns
                        </x-nav-link>
                        <x-nav-link :href="route('rewards.index')" :active="request()->routeIs('rewards.*')">
                            Rewards
                        </x-nav-link>
                    @else
                        <x-nav-link :href="route('visits.create')" :active="request()->routeIs('visits.*')">
                            Check in
                        </x-nav-link>
                        <x-nav-link :href="route('memberships.index')" :active="request()->routeIs('memberships.*')">
                            My wallets
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-xl border border-ink/10 bg-white px-3 py-2 text-sm font-medium text-ink-muted transition hover:text-ink">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                        @if (Auth::user()->isBusiness() && Auth::user()->business)
                            <x-dropdown-link :href="route('business.edit')">Business settings</x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Log out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="sm:hidden">
                <button @click="open = ! open" class="rounded-lg p-2 text-ink-muted hover:bg-chalk-warm">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-ink/5 sm:hidden">
        <div class="space-y-1 px-4 py-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            @if (Auth::user()->isBusiness())
                <x-responsive-nav-link :href="route('shops.index')">Shops</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('campaigns.index')">Campaigns</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('rewards.index')">Rewards</x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('visits.create')">Check in</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('memberships.index')">My wallets</x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('profile.edit')">Profile</x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                    Log out
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
