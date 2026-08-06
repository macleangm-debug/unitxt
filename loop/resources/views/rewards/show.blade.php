<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.offers') }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-3xl font-semibold">{{ $reward->name }}</h1>
                    @if ($reward->is_active)
                        <span class="rounded-lg bg-mint px-2.5 py-1 text-xs font-semibold text-ink">{{ __('loop.live') }}</span>
                    @endif
                </div>
                <p class="mt-1 text-ink-muted">{{ $reward->label() }} · {{ $reward->points_cost }} pts</p>
            </div>
            <a href="{{ route('campaigns.index') }}#offers" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-gradient-to-br from-mint/30 to-mint/5 p-5 ring-1 ring-mint/20">
            <p class="text-sm text-ink-muted">{{ __('loop.redemptions') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['total_redemptions'] }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-coral/25 to-coral/5 p-5 ring-1 ring-coral/20">
            <p class="text-sm text-ink-muted">{{ __('loop.this_month') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['this_month'] }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-ink/90 to-ink p-5 text-white">
            <p class="text-sm text-white/70">{{ __('loop.points_spent') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['points_spent']) }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-mint to-mint-deep p-5 text-ink">
            <p class="text-sm text-ink/70">{{ __('loop.stock') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['stock'] ?? __('loop.unlimited') }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="overflow-hidden rounded-[1.75rem] border border-ink/10 bg-white/90">
            <div class="bg-gradient-to-r from-mint/25 via-mint/5 to-transparent px-6 py-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.offer_details') }}</h2>
            </div>
            <dl class="space-y-3 px-6 py-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('loop.type') }}</dt>
                    <dd class="font-semibold">{{ __('loop.'.$reward->reward_type) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('loop.points_cost') }}</dt>
                    <dd class="font-semibold">{{ $reward->points_cost }}</dd>
                </div>
                @if ($reward->product_name)
                    <div class="flex justify-between gap-4">
                        <dt class="text-ink-muted">{{ __('loop.product') }}</dt>
                        <dd class="font-semibold">{{ $reward->product_name }}</dd>
                    </div>
                @endif
                @if ($reward->description)
                    <p class="border-t border-ink/5 pt-3 text-ink-muted">{{ $reward->description }}</p>
                @endif
            </dl>
        </section>

        <section class="overflow-hidden rounded-[1.75rem] border border-ink/10 bg-white/90">
            <div class="bg-gradient-to-r from-coral/20 via-coral/5 to-transparent px-6 py-4">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.whats_working') }}</h2>
            </div>
            <div class="px-6 py-5 text-sm text-ink-muted">
                <p>{{ __('loop.offer_stats_blurb') }}</p>
                <p class="mt-4 rounded-xl bg-mint-soft/60 px-3 py-2 font-medium text-ink">
                    {{ __('loop.most_used_hint', ['count' => $stats['total_redemptions']]) }}
                </p>
            </div>
        </section>
    </div>

    <section class="mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_redemptions') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($recentRedemptions as $redemption)
                <div class="loop-panel flex items-center justify-between gap-4 px-4 py-3">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $redemption->customer?->name ?? __('loop.customer') }}</p>
                        <p class="text-xs text-ink-muted">{{ $redemption->created_at->format('d M Y · H:i') }}
                            @if ($redemption->visit?->shop)
                                · {{ $redemption->visit->shop->name }}
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 font-display text-lg font-semibold text-coral">−{{ $redemption->points_spent }}</span>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_redemptions_yet') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
