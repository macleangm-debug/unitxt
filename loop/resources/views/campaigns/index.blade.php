<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">Campaigns</h1>
                <p class="mt-1 text-ink-muted">Point rules that reward customers every time they visit your shops.</p>
            </div>
            <a href="{{ route('campaigns.create') }}" class="loop-btn-mint">New campaign</a>
        </div>
    </x-slot>

    <div class="grid gap-4">
        @forelse ($campaigns as $campaign)
            <div class="loop-panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-display text-lg font-semibold">{{ $campaign->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ $campaign->pointsForVisit() }} pts / visit
                            · max {{ $campaign->max_visits_per_day }}/day
                            · {{ $campaign->starts_at->toFormattedDateString() }}
                            @if ($campaign->ends_at)
                                → {{ $campaign->ends_at->toFormattedDateString() }}
                            @endif
                        </p>
                        <p class="mt-2 text-sm text-ink-muted">
                            Shops:
                            @if ($campaign->shops->isEmpty())
                                All shops
                            @else
                                {{ $campaign->shops->pluck('name')->join(', ') }}
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $campaign->isCurrentlyActive() ? 'bg-mint-soft text-ink' : 'bg-chalk text-ink-muted' }}">
                            {{ $campaign->isCurrentlyActive() ? 'Live' : 'Off' }}
                        </span>
                        <a href="{{ route('campaigns.edit', $campaign) }}" class="loop-btn-ghost !px-3 !py-2">Edit</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="loop-panel p-8 text-center">
                <p class="text-ink-muted">Create a campaign to start awarding points on shop visits.</p>
                <a href="{{ route('campaigns.create') }}" class="loop-btn mt-4">New campaign</a>
            </div>
        @endforelse
    </div>
</x-app-layout>
