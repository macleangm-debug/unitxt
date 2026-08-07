<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-8">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.campaigns') }}</p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-4xl font-semibold tracking-tight sm:text-5xl">{{ $campaign->displayName() }}</h1>
                    @if ($campaign->isCurrentlyActive())
                        <span class="rounded-full bg-mint px-3 py-1 text-xs font-semibold text-ink">{{ __('loop.live') }}</span>
                    @endif
                </div>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('campaigns.index') }}" class="loop-btn-ghost !py-2.5">{{ __('loop.back') }}</a>
                <a href="{{ route('campaigns.edit', $campaign) }}" class="loop-btn-mint !py-2.5">{{ __('loop.edit') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[2rem] bg-gradient-to-br from-mint/30 via-mint/10 to-white p-7 ring-1 ring-mint/20">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.today') }}</p>
            <p class="mt-6 font-display text-5xl font-semibold tracking-tight">{{ $stats['today_visits'] }}</p>
        </div>
        <div class="rounded-[2rem] bg-gradient-to-br from-coral/25 via-coral/8 to-white p-7 ring-1 ring-coral/20">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.sales') }}</p>
            <p class="mt-6 font-display text-5xl font-semibold tracking-tight">{{ $stats['total_visits'] }}</p>
        </div>
        <div class="rounded-[2rem] bg-ink p-7 text-white shadow-[0_24px_60px_rgba(11,31,42,0.18)]">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/55">{{ __('loop.spend') }}</p>
            <p class="mt-6 font-display text-4xl font-semibold tracking-tight sm:text-5xl">{{ number_format($stats['total_spend'], 0) }}</p>
            <p class="mt-2 text-sm text-white/50">{{ $business->currency }}</p>
        </div>
        <div class="rounded-[2rem] bg-gradient-to-br from-mint to-mint-deep p-7 text-ink shadow-[0_24px_60px_rgba(45,212,168,0.28)]">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink/55">{{ __('loop.points_awarded') }}</p>
            <p class="mt-6 font-display text-5xl font-semibold tracking-tight">{{ number_format($stats['points_awarded']) }}</p>
        </div>
    </div>

    <div class="mt-14 grid gap-10 lg:grid-cols-2">
        <section class="rounded-[2rem] border border-ink/8 bg-white/95 p-8 shadow-[0_20px_60px_rgba(11,31,42,0.05)] sm:p-9">
            <h2 class="font-display text-2xl font-semibold">{{ __('loop.schedule') }}</h2>
            <dl class="mt-8 space-y-5 text-sm">
                <div class="flex items-center justify-between gap-4 border-b border-ink/5 pb-5">
                    <dt class="text-ink-muted">{{ __('loop.status') }}</dt>
                    <dd class="font-semibold">{{ $campaign->isCurrentlyActive() ? __('loop.live') : __('loop.off') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-ink/5 pb-5">
                    <dt class="text-ink-muted">{{ __('loop.starts') }}</dt>
                    <dd class="font-semibold">{{ $campaign->starts_at?->format('d M Y') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-b border-ink/5 pb-5">
                    <dt class="text-ink-muted">{{ __('loop.ends') }}</dt>
                    <dd class="font-semibold">{{ $campaign->ends_at?->format('d M Y') ?? __('loop.open_ended') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('loop.type') }}</dt>
                    <dd class="font-semibold">{{ __('loop.type_'.$campaign->type) }}</dd>
                </div>
            </dl>
            @if ($campaign->displayDescription())
                <p class="mt-8 rounded-2xl bg-chalk/80 px-5 py-4 text-sm leading-relaxed text-ink-muted">{{ $campaign->displayDescription() }}</p>
            @endif
        </section>

        <section class="rounded-[2rem] border border-ink/8 bg-white/95 p-8 shadow-[0_20px_60px_rgba(11,31,42,0.05)] sm:p-9">
            <h2 class="font-display text-2xl font-semibold">{{ __('loop.whats_working') }}</h2>
            <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-muted">{{ __('loop.whats_working_body') }}</p>
            <div class="mt-8 space-y-4">
                <div class="rounded-2xl bg-mint-soft/80 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.avg_sale_amount') }}</p>
                    <p class="mt-2 font-display text-3xl font-semibold tracking-tight">{{ $business->currency }} {{ number_format($stats['avg_ticket'], 0) }}</p>
                </div>
                <div class="rounded-2xl bg-coral/10 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.shops') }}</p>
                    <p class="mt-2 font-display text-xl font-semibold leading-snug">
                        @if ($campaign->shops->isEmpty())
                            {{ __('loop.all_shops') }}
                        @else
                            {{ $campaign->shops->pluck('name')->join(', ') }}
                        @endif
                    </p>
                </div>
            </div>
        </section>
    </div>

    <section class="mt-14 rounded-[2rem] border border-ink/8 bg-white/95 p-8 shadow-[0_20px_60px_rgba(11,31,42,0.05)] sm:p-9">
        <h2 class="font-display text-2xl font-semibold">{{ __('loop.tied_offers') }}</h2>
        <p class="mt-2 text-sm text-ink-muted">{{ __('loop.tie_offers_body') }}</p>
        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            @forelse ($campaign->rewards as $offer)
                <div class="rounded-2xl bg-mint-soft/60 px-5 py-4">
                    <p class="font-semibold">{{ $offer->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $offer->points_cost }} {{ __('loop.pts') }} · {{ $offer->label() }}</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_tied_offers') }}</p>
            @endforelse
        </div>
    </section>

    @if ($recentVisits->isNotEmpty())
        <section class="mt-14">
            <div class="mb-6 flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-2xl font-semibold">{{ __('loop.recent_sales') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.transactions_blurb') }}</p>
                </div>
                <a href="{{ route('transactions.index') }}" class="shrink-0 text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }} →</a>
            </div>
            <div class="space-y-4">
                @foreach ($recentVisits as $visit)
                    <div class="grid grid-cols-[1fr_auto] items-center gap-6 rounded-[1.75rem] border border-ink/8 bg-white/95 px-6 py-5 shadow-[0_12px_40px_rgba(11,31,42,0.04)]">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $visit->customer->name }}</p>
                            <p class="mt-1.5 text-sm text-ink-muted">{{ $visit->shop->name }} · {{ $visit->created_at->format('d M Y · H:i') }}</p>
                            <p class="mt-2 inline-flex rounded-full bg-mint-soft px-2.5 py-0.5 text-xs font-semibold text-mint-deep">+{{ $visit->points_earned }} pts</p>
                        </div>
                        <p class="shrink-0 text-right font-display text-2xl font-semibold tracking-tight sm:text-3xl">
                            {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
