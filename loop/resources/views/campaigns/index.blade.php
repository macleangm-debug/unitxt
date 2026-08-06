<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">Campaigns</h1>
                <p class="mt-1 text-ink-muted">Start from a proven template, or build your own rules.</p>
            </div>
            <a href="{{ route('campaigns.create') }}" class="loop-btn-mint">New campaign</a>
        </div>
    </x-slot>

    <section class="mb-8">
        <h2 class="mb-3 font-display text-lg font-semibold">Proven to work</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($templates as $key => $template)
                <a href="{{ route('campaigns.create', ['template' => $key]) }}" class="loop-panel block p-4 transition hover:bg-white">
                    <p class="font-semibold">{{ $template['name'] }}</p>
                    <p class="mt-1 text-xs text-ink-muted">{{ $template['description'] }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <div class="grid gap-4">
        @forelse ($campaigns as $campaign)
            <div class="loop-panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-lg font-semibold">{{ $campaign->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $campaign->isCurrentlyActive() ? 'bg-mint-soft' : 'bg-chalk text-ink-muted' }}">
                            {{ $campaign->isCurrentlyActive() ? 'Live' : 'Off' }}
                        </span>
                        <a href="{{ route('campaigns.edit', $campaign) }}" class="loop-btn-ghost !px-3 !py-2">Edit</a>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-ink-muted">No campaigns yet — pick a template above.</p>
        @endforelse
    </div>
</x-app-layout>
