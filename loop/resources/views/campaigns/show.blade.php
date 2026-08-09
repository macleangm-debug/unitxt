<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.campaigns') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $campaign->displayName() }}</h1>
                    @if ($campaign->isCurrentlyActive())
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-mint px-3.5 py-1.5 text-sm font-bold uppercase tracking-wide text-ink shadow-[0_0_0_4px_rgba(46,125,50,0.18)]">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-ink"></span>
                            {{ __('loop.live') }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-ink/10 px-3.5 py-1.5 text-sm font-bold uppercase tracking-wide text-ink-muted">
                            {{ __('loop.paused') }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-ink-muted">{{ $campaign->scheduleLabel() }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('campaigns.index') }}" class="loop-btn-ghost !py-2.5">{{ __('loop.back') }}</a>
                <form method="POST" action="{{ route('campaigns.toggle', $campaign) }}">
                    @csrf
                    <button class="loop-btn-ghost !py-2.5">
                        {{ $campaign->is_active ? __('loop.pause_campaign') : __('loop.resume_campaign') }}
                    </button>
                </form>
                <a href="{{ route('campaigns.edit', $campaign) }}" class="loop-btn-mint !py-2.5">{{ __('loop.edit') }}</a>
            </div>
        </div>
    </x-slot>

    @if (in_array($campaign->type, ['earn', 'product_push'], true) && $campaign->spend_step && $campaign->points_per_step)
        <div class="mt-6 rounded-[1.5rem] border border-ink/10 bg-white px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.customer_gets') }}</p>
            <p class="mt-2 font-display text-2xl font-bold text-ink sm:text-3xl">
                {{ $campaign->points_per_step }} {{ __('loop.pts') }}
                <span class="text-ink-muted">/</span>
                {{ $business->currency }} {{ number_format($campaign->spend_step) }}
            </p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.min_spend_to_earn_hint', ['currency' => $business->currency, 'amount' => number_format($campaign->spend_step)]) }}</p>
            @if ($campaign->type === 'product_push' && $campaign->featured_product_name)
                <p class="mt-2 text-sm text-ink-muted">
                    {{ __('loop.rule_featured_product', ['product' => $campaign->featured_product_name, 'points' => $campaign->bonus_points]) }}
                </p>
            @endif
        </div>
    @elseif ($campaign->ruleSummary($business->currency))
        <p class="mt-6 text-base font-medium text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
    @endif

    <div class="mt-6 grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.today') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $stats['today_visits'] }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $stats['total_visits'] }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.spend') }}</p>
            <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ number_format($stats['total_spend'], 0) }}</p>
            <p class="mt-0.5 text-[10px] text-ink-muted">{{ $business->currency }}</p>
        </div>
    </div>

    @if ($recentVisits->isNotEmpty())
        <section class="mt-8">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_sales') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.campaign_sales_blurb') }}</p>
                </div>
                <a href="{{ route('transactions.index') }}" class="shrink-0 text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }} →</a>
            </div>
            <div class="space-y-3">
                @foreach ($recentVisits as $visit)
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-ink/10 bg-white px-4 py-3.5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $visit->customer->name }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">{{ $visit->created_at->format('d M · H:i') }} · +{{ $visit->points_earned }} pts</p>
                        </div>
                        <p class="shrink-0 font-display text-lg font-semibold">
                            {{ number_format($visit->amount_spent, 0) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
