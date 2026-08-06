<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.campaigns') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $campaign->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('campaigns.index') }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
                <a href="{{ route('campaigns.edit', $campaign) }}" class="loop-btn-mint !py-2">{{ __('loop.edit') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="loop-panel p-5">
            <p class="text-sm text-ink-muted">{{ __('loop.today') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['today_visits'] }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-sm text-ink-muted">{{ __('loop.visits') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['total_visits'] }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-sm text-ink-muted">{{ __('loop.spend') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['total_spend'], 0) }}</p>
            <p class="text-xs text-ink-muted">{{ $business->currency }}</p>
        </div>
        <div class="loop-panel p-5">
            <p class="text-sm text-ink-muted">{{ __('loop.points_awarded') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['points_awarded']) }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="loop-panel p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.schedule') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('loop.status') }}</dt>
                    <dd class="font-semibold">{{ $campaign->isCurrentlyActive() ? __('loop.live') : __('loop.off') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('loop.starts') }}</dt>
                    <dd class="font-semibold">{{ $campaign->starts_at?->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('loop.ends') }}</dt>
                    <dd class="font-semibold">{{ $campaign->ends_at?->format('d M Y') ?? __('loop.open_ended') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('loop.type') }}</dt>
                    <dd class="font-semibold">{{ $campaign->type }}</dd>
                </div>
            </dl>
            @if ($campaign->description)
                <p class="mt-4 text-sm text-ink-muted">{{ $campaign->description }}</p>
            @endif
        </section>

        <section class="loop-panel p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.whats_working') }}</h2>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.whats_working_body') }}</p>
            <ul class="mt-4 space-y-2 text-sm">
                <li class="rounded-xl bg-chalk px-3 py-2">
                    {{ __('loop.avg_ticket') }}:
                    <span class="font-semibold">{{ $business->currency }} {{ number_format($stats['avg_ticket'], 0) }}</span>
                </li>
                <li class="rounded-xl bg-chalk px-3 py-2">
                    {{ __('loop.shops') }}:
                    <span class="font-semibold">
                        @if ($campaign->shops->isEmpty())
                            {{ __('loop.all_shops') }}
                        @else
                            {{ $campaign->shops->pluck('name')->join(', ') }}
                        @endif
                    </span>
                </li>
            </ul>
        </section>
    </div>

    <section class="mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_visits') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($recentVisits as $visit)
                <div class="loop-panel flex items-center justify-between px-4 py-3">
                    <div>
                        <p class="font-semibold">{{ $visit->customer->name }}</p>
                        <p class="text-sm text-ink-muted">
                            {{ $visit->shop->name }} · {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                            · {{ $visit->created_at->format('d M Y · H:i') }}
                        </p>
                    </div>
                    <span class="text-sm font-semibold text-mint-deep">+{{ $visit->points_earned }}</span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_campaign_sales') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
