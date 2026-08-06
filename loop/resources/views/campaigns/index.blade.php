<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.campaigns') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.campaigns_blurb') }}</p>
            </div>
            <a href="{{ route('campaigns.create') }}" class="loop-btn-mint">{{ __('loop.new_campaign') }}</a>
        </div>
    </x-slot>

    <div class="space-y-3">
        @forelse ($campaigns as $campaign)
            <a href="{{ route('campaigns.show', $campaign) }}" class="loop-panel flex items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:bg-white">
                <div class="min-w-0">
                    <p class="font-display text-lg font-semibold">{{ $campaign->displayName() }}</p>
                    <p class="mt-0.5 truncate text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                </div>
                @if ($campaign->isCurrentlyActive())
                    <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-xs font-semibold text-ink">{{ __('loop.live') }}</span>
                @endif
            </a>
        @empty
            <div class="loop-panel p-6 text-center">
                <p class="text-sm text-ink-muted">{{ __('loop.no_campaigns_yet') }}</p>
                <a href="{{ route('campaigns.create') }}" class="loop-btn-mint mt-4 inline-flex">{{ __('loop.new_campaign') }}</a>
            </div>
        @endforelse
    </div>
</x-app-layout>
