<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.campaigns') }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-3xl font-semibold">{{ $campaign->name }}</h1>
                    @if ($campaign->isCurrentlyActive())
                        <span class="rounded-lg bg-mint px-2.5 py-1 text-xs font-semibold text-ink">{{ __('loop.live') }}</span>
                    @endif
                </div>
                <p class="mt-1 text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('campaigns.index') }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
                <a href="{{ route('campaigns.edit', $campaign) }}" class="loop-btn-mint !py-2">{{ __('loop.edit') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-gradient-to-br from-mint/30 to-mint/5 p-5 ring-1 ring-mint/20">
            <p class="text-sm text-ink-muted">{{ __('loop.today') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['today_visits'] }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-coral/25 to-coral/5 p-5 ring-1 ring-coral/20">
            <p class="text-sm text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['total_visits'] }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-ink/90 to-ink p-5 text-white">
            <p class="text-sm text-white/70">{{ __('loop.spend') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['total_spend'], 0) }}</p>
            <p class="text-xs text-white/55">{{ $business->currency }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-mint to-mint-deep p-5 text-ink">
            <p class="text-sm text-ink/70">{{ __('loop.points_awarded') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['points_awarded']) }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="loop-panel overflow-hidden p-0">
            <div class="bg-gradient-to-r from-mint/20 to-transparent px-6 py-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.schedule') }}</h2>
            </div>
            <dl class="space-y-3 px-6 py-5 text-sm">
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
                <p class="border-t border-ink/5 px-6 py-4 text-sm text-ink-muted">{{ $campaign->description }}</p>
            @endif
        </section>

        <section class="loop-panel overflow-hidden p-0">
            <div class="bg-gradient-to-r from-coral/15 to-transparent px-6 py-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.whats_working') }}</h2>
            </div>
            <div class="px-6 py-5">
                <p class="text-sm text-ink-muted">{{ __('loop.whats_working_body') }}</p>
                <ul class="mt-4 space-y-2 text-sm">
                    <li class="rounded-xl bg-mint-soft/60 px-3 py-2">
                        {{ __('loop.avg_ticket') }}:
                        <span class="font-semibold">{{ $business->currency }} {{ number_format($stats['avg_ticket'], 0) }}</span>
                    </li>
                    <li class="rounded-xl bg-coral/10 px-3 py-2">
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
            </div>
        </section>
    </div>

    <section class="mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
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
                    <span class="rounded-lg bg-mint-soft px-2.5 py-1 text-sm font-semibold text-mint-deep">+{{ $visit->points_earned }}</span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_campaign_sales') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
