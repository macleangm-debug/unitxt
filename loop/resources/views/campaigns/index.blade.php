@php
    $tab = $tab ?? 'campaigns';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 max-w-2xl">
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.campaigns_and_offers') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.campaigns_and_offers_blurb') }}</p>
            </div>
            @if ($tab === 'offers')
                <a href="{{ route('rewards.create') }}" class="loop-btn-mint shrink-0 !py-2.5">{{ __('loop.add_offer') }}</a>
            @else
                <a href="{{ route('campaigns.create') }}" class="loop-btn-mint shrink-0 !py-2.5">{{ __('loop.new_campaign') }}</a>
            @endif
        </div>
    </x-slot>

    <div class="mb-5 grid grid-cols-3 gap-3">
        @if ($tab === 'offers')
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.live_offers') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $offerStats['live'] }}</p>
            </div>
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.redemptions') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $offerStats['redemptions'] }}</p>
            </div>
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.points_spent') }}</p>
                <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ number_format($offerStats['points_spent'], 0) }}</p>
                <p class="mt-0.5 text-[10px] text-ink-muted">{{ __('loop.pts') }}</p>
            </div>
        @else
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.live_campaigns') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $campaignStats['live'] }}</p>
            </div>
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.sales') }}</p>
                <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $campaignStats['sales'] }}</p>
            </div>
            <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.spend') }}</p>
                <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ number_format($campaignStats['spend'], 0) }}</p>
                <p class="mt-0.5 text-[10px] text-ink-muted">{{ $business->currency }}</p>
            </div>
        @endif
    </div>

    <div class="mb-5 flex gap-2">
        <a
            href="{{ route('campaigns.index') }}"
            class="min-w-0 flex-1 rounded-2xl px-3 py-2.5 text-center text-sm font-semibold transition {{ $tab === 'campaigns' ? 'bg-ink text-white' : 'bg-white text-ink-muted ring-1 ring-ink/10' }}"
        >{{ __('loop.how_they_earn') }}</a>
        <a
            href="{{ route('campaigns.index', ['tab' => 'offers']) }}"
            class="min-w-0 flex-1 rounded-2xl px-3 py-2.5 text-center text-sm font-semibold transition {{ $tab === 'offers' ? 'bg-ink text-white' : 'bg-white text-ink-muted ring-1 ring-ink/10' }}"
        >{{ __('loop.what_they_choose') }}</a>
    </div>

    @if ($tab === 'offers')
        <div class="space-y-3">
            @forelse ($rewards as $reward)
                <a href="{{ route('rewards.show', $reward) }}" class="flex items-center justify-between gap-4 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4 transition hover:-translate-y-0.5 hover:bg-white">
                    <div class="min-w-0">
                        <p class="font-display text-lg font-semibold">{{ $reward->name }}</p>
                        <p class="mt-0.5 truncate text-sm text-ink-muted">
                            {{ $reward->label() }}
                            · {{ $reward->points_cost }} {{ __('loop.pts') }}
                            @if ($reward->product_name)
                                · {{ $reward->product_name }}
                            @endif
                        </p>
                        <div class="mt-2">
                            <x-status-pill :live="$reward->is_active" />
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-display text-2xl font-semibold tracking-tight">{{ number_format((int) $reward->redemptions_count) }}</p>
                        <p class="text-xs text-ink-muted">{{ __('loop.redemptions') }}</p>
                    </div>
                </a>
            @empty
                <div class="loop-panel p-8 text-center">
                    <p class="font-display text-lg font-semibold">{{ __('loop.no_offers_yet_hint') }}</p>
                    <a href="{{ route('rewards.create') }}" class="loop-btn-mint mt-5 inline-flex">{{ __('loop.add_offer') }}</a>
                </div>
            @endforelse
        </div>
    @else
        <div class="space-y-3">
            @forelse ($campaigns as $campaign)
                <a href="{{ route('campaigns.show', $campaign) }}" class="flex items-center justify-between gap-4 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4 transition hover:-translate-y-0.5 hover:bg-white">
                    <div class="min-w-0">
                        <p class="font-display text-lg font-semibold">{{ $campaign->displayName() }}</p>
                        <p class="mt-0.5 truncate text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                        <div class="mt-2">
                            <x-status-pill :live="$campaign->isCurrentlyActive()" />
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-display text-2xl font-semibold tracking-tight">{{ number_format((int) $campaign->visits_count) }}</p>
                        <p class="text-xs text-ink-muted">{{ __('loop.sales') }}</p>
                    </div>
                </a>
            @empty
                <div class="loop-panel p-8 text-center">
                    <p class="font-display text-lg font-semibold">{{ __('loop.no_campaigns_yet') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.how_they_earn_body') }}</p>
                    <a href="{{ route('campaigns.create') }}" class="loop-btn-mint mt-5 inline-flex">{{ __('loop.new_campaign') }}</a>
                </div>
            @endforelse
        </div>
    @endif
</x-app-layout>
