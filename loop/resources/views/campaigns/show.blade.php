<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.campaigns') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $campaign->displayName() }}</h1>
                    @if ($campaign->isCurrentlyActive())
                        <span class="rounded-full bg-mint px-3 py-1 text-xs font-bold uppercase tracking-wide text-ink">{{ __('loop.live') }}</span>
                    @endif
                </div>
                @if (in_array($campaign->type, ['earn', 'product_push'], true) && $campaign->spend_step && $campaign->points_per_step)
                    <div class="mt-5 rounded-[1.5rem] bg-mint-soft px-5 py-4 ring-1 ring-mint/25">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.customer_gets') }}</p>
                        <p class="mt-1 font-display text-2xl font-bold text-ink sm:text-3xl">
                            {{ $campaign->points_per_step }} {{ __('loop.pts') }}
                            <span class="text-ink-muted">/</span>
                            {{ $business->currency }} {{ number_format($campaign->spend_step) }}
                        </p>
                        @if ($campaign->type === 'product_push' && $campaign->featured_product_name)
                            <p class="mt-2 text-sm font-medium text-ink-muted">
                                {{ __('loop.rule_featured_product', ['product' => $campaign->featured_product_name, 'points' => $campaign->bonus_points]) }}
                            </p>
                        @endif
                    </div>
                @elseif ($campaign->ruleSummary($business->currency))
                    <p class="mt-4 text-base font-medium text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('campaigns.index') }}" class="loop-btn-ghost !py-2.5">{{ __('loop.back') }}</a>
                <a href="{{ route('campaigns.edit', $campaign) }}" class="loop-btn-mint !py-2.5">{{ __('loop.edit') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="mt-6 grid gap-3 grid-cols-3">
        <div class="rounded-2xl bg-gradient-to-br from-mint/25 to-white p-4 ring-1 ring-mint/15 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.today') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $stats['today_visits'] }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-coral/20 to-white p-4 ring-1 ring-coral/15 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $stats['total_visits'] }}</p>
        </div>
        <div class="rounded-2xl bg-ink p-4 text-white sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-white/55">{{ __('loop.spend') }}</p>
            <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ number_format($stats['total_spend'], 0) }}</p>
            <p class="mt-0.5 text-[10px] text-white/45">{{ $business->currency }}</p>
        </div>
    </div>

    <section class="mt-8 rounded-[1.75rem] border border-ink/8 bg-white/95 p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.offers') }}</h2>
            <a href="{{ route('rewards.create') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.add_offer') }} →</a>
        </div>
        <div class="mt-4 space-y-2">
            @forelse ($campaign->rewards as $offer)
                <a href="{{ route('rewards.show', $offer) }}" class="flex items-center justify-between gap-3 rounded-2xl bg-mint-soft/50 px-4 py-3 transition hover:bg-mint-soft">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $offer->name }}</p>
                        <p class="mt-0.5 truncate text-sm text-ink-muted">
                            {{ $offer->points_cost }} {{ __('loop.pts') }} · {{ $offer->label() }}
                            @if ($offer->product_name)
                                · {{ $offer->product_name }}
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 text-xs font-semibold text-violet">{{ __('loop.change') }}</span>
                </a>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_tied_offers') }}</p>
            @endforelse
        </div>
    </section>

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.campaign_sales_blurb') }}</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="shrink-0 text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }} →</a>
        </div>
        @forelse ($recentVisits as $visit)
            <div class="mb-3 flex items-center justify-between gap-4 rounded-2xl border border-ink/8 bg-white/95 px-4 py-3.5">
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ $visit->customer->name }}</p>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $visit->created_at->format('d M · H:i') }} · +{{ $visit->points_earned }} pts</p>
                </div>
                <p class="shrink-0 font-display text-lg font-semibold">
                    {{ number_format($visit->amount_spent, 0) }}
                </p>
            </div>
        @empty
            <p class="rounded-2xl bg-chalk/70 px-4 py-6 text-center text-sm text-ink-muted">{{ __('loop.no_campaign_sales_yet') }}</p>
        @endforelse
    </section>
</x-app-layout>
