<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ $business->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $business->sectorLabel() }} · {{ $business->city }} · {{ $business->currency }}</p>
            </div>
            <a href="{{ route('till.index') }}" class="loop-btn-mint">Open till</a>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-panel p-5"><p class="text-sm text-ink-muted">Shops</p><p class="mt-2 font-display text-3xl font-semibold">{{ $shopCount }}</p></div>
        <div class="loop-panel p-5"><p class="text-sm text-ink-muted">Campaigns</p><p class="mt-2 font-display text-3xl font-semibold">{{ $campaignCount }}</p></div>
        <div class="loop-panel p-5"><p class="text-sm text-ink-muted">Members</p><p class="mt-2 font-display text-3xl font-semibold">{{ $memberCount }}</p></div>
        <div class="loop-panel p-5"><p class="text-sm text-ink-muted">Visits</p><p class="mt-2 font-display text-3xl font-semibold">{{ $visitCount }}</p></div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="loop-panel p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">Active campaigns</h2>
                @if ($isOwner)
                    <a href="{{ route('campaigns.create') }}" class="text-sm font-semibold text-mint-deep">New</a>
                @endif
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($activeCampaigns as $campaign)
                    <div class="rounded-xl bg-chalk px-4 py-3">
                        <p class="font-semibold">{{ $campaign->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No live campaigns.</p>
                @endforelse
            </div>
        </section>
        <section class="loop-panel p-6">
            <h2 class="font-display text-xl font-semibold">Recent visits</h2>
            <div class="mt-4 space-y-3">
                @forelse ($recentVisits as $visit)
                    <div class="flex items-center justify-between rounded-xl bg-chalk px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $visit->customer->name }}</p>
                            <p class="text-sm text-ink-muted">{{ $visit->shop->name }} · {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}</p>
                        </div>
                        <span class="text-sm font-semibold text-mint-deep">+{{ $visit->points_earned }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">Till activity will show here.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
