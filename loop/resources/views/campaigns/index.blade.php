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
                    <p class="font-display text-lg font-semibold">{{ $campaign->name }}</p>
                    <p class="mt-0.5 truncate text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                </div>
                @if ($campaign->isCurrentlyActive())
                    <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-xs font-semibold text-ink">{{ __('loop.live') }}</span>
                @endif
            </a>
        @empty
            <div class="loop-panel p-6 text-sm text-ink-muted">{{ __('loop.no_campaigns_yet') }}</div>
        @endforelse
    </div>

    <section class="mt-10">
        <h2 class="mb-3 font-display text-lg font-semibold">{{ __('loop.proven_templates') }}</h2>
        <p class="mb-4 text-sm text-ink-muted">{{ __('loop.proven_templates_hint') }}</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($templates as $key => $template)
                <a href="{{ route('campaigns.create', ['template' => $key]) }}" class="rounded-2xl border border-dashed border-ink/15 bg-white/50 p-4 transition hover:border-mint hover:bg-mint-soft/30">
                    <p class="font-semibold">{{ $template['name'] }}</p>
                    <p class="mt-1 text-xs text-ink-muted">{{ $template['description'] }}</p>
                </a>
            @endforeach
        </div>
    </section>
</x-app-layout>
