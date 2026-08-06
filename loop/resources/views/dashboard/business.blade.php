<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold text-ink">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-ink-muted">Your loyalty operations at a glance.</p>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 animate-fade-up">
        <div class="loop-panel p-5">
            <p class="text-sm text-ink-muted">Shops</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $shopCount }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-sm text-ink-muted">Campaigns</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $campaignCount }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-sm text-ink-muted">Members</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $memberCount }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-sm text-ink-muted">Visits</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $visitCount }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="loop-panel p-6 animate-fade-up-delay">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">Active campaigns</h2>
                <a href="{{ route('campaigns.create') }}" class="text-sm font-semibold text-mint-deep hover:underline">New campaign</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($activeCampaigns as $campaign)
                    <div class="rounded-xl bg-chalk px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $campaign->name }}</p>
                                <p class="text-sm text-ink-muted">{{ $campaign->pointsForVisit() }} pts / visit · {{ $campaign->shops_count ?: 'All' }} shops</p>
                            </div>
                            <a href="{{ route('campaigns.edit', $campaign) }}" class="text-sm text-ink-muted hover:text-ink">Edit</a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No active campaigns yet. Launch one to start awarding points.</p>
                @endforelse
            </div>
        </section>

        <section class="loop-panel p-6 animate-fade-up-delay-2">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">Recent visits</h2>
                <a href="{{ route('shops.index') }}" class="text-sm font-semibold text-mint-deep hover:underline">Manage shops</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($recentVisits as $visit)
                    <div class="flex items-center justify-between rounded-xl bg-chalk px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $visit->customer->name }}</p>
                            <p class="text-sm text-ink-muted">{{ $visit->shop->name }} · {{ $visit->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="rounded-lg bg-mint-soft px-2.5 py-1 text-sm font-semibold text-ink">+{{ $visit->points_earned }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">Visits will appear here once customers check in.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
