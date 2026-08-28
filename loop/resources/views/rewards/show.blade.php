<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.offers') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $reward->name }}</h1>
                    <x-status-pill :live="$reward->is_active" size="lg" />
                </div>
                <p class="mt-2 text-sm text-ink-muted">{{ $reward->label() }} · {{ number_format((int) $reward->points_cost) }} {{ __('loop.pts') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-back-icon :href="route('campaigns.index', ['tab' => 'offers'])" />
                @if ($reward->is_active)
                    <x-pause-confirm
                        :action="route('rewards.toggle', $reward)"
                        :title="__('loop.pause_offer_confirm_title')"
                        :body="__('loop.pause_offer_confirm_body')"
                    />
                @else
                    <form method="POST" action="{{ route('rewards.toggle', $reward) }}">
                        @csrf
                        <button class="loop-btn-ghost !py-2.5">{{ __('loop.resume_campaign') }}</button>
                    </form>
                @endif
                <a href="{{ route('rewards.edit', $reward) }}" class="loop-btn-mint !py-2.5">{{ __('loop.edit') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="mt-6 rounded-[1.5rem] border border-ink/10 bg-white px-5 py-5">
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.member_redeems') }}</p>
        <p class="mt-2 font-display text-2xl font-bold text-ink sm:text-3xl">
            {{ number_format((int) $reward->points_cost) }} {{ __('loop.pts') }}
        </p>
        <p class="mt-2 text-sm text-ink-muted">{{ $reward->label() }} · {{ __('loop.offer_hero_hint', ['points' => number_format((int) $reward->points_cost)]) }}</p>
        @if ($reward->product_name)
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.product') }}: <span class="font-semibold text-ink">{{ $reward->product_name }}</span></p>
        @endif
        @if ($reward->description)
            <p class="mt-2 text-sm text-ink-muted">{{ $reward->description }}</p>
        @endif
        <p class="mt-2 text-sm text-ink-muted">
            {{ __('loop.stock') }}:
            <span class="font-semibold text-ink">{{ $stats['stock'] ?? __('loop.unlimited') }}</span>
        </p>
    </div>

    <div class="mt-6 grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.redemptions') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $stats['total_redemptions'] }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.this_month') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $stats['this_month'] }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.points_spent') }}</p>
            <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ number_format($stats['points_spent'], 0) }}</p>
            <p class="mt-0.5 text-[10px] text-ink-muted">{{ __('loop.pts') }}</p>
        </div>
    </div>

    @if ($recentRedemptions->isNotEmpty())
        <section class="mt-8">
            <div class="mb-4 flex items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_redemptions') }}</h2>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.offer_redemptions_blurb') }}</p>
                </div>
            </div>
            <div class="space-y-3">
                @foreach ($recentRedemptions as $redemption)
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-ink/10 bg-white px-4 py-3.5">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $redemption->customer?->name ?? __('loop.customer') }}</p>
                            <p class="mt-0.5 text-xs text-ink-muted">
                                {{ $redemption->created_at->format('d M · H:i') }}
                                @if ($redemption->shop)
                                    · {{ $redemption->shop->name }}
                                @elseif ($redemption->visit?->shop)
                                    · {{ $redemption->visit->shop->name }}
                                @endif
                                · −{{ $redemption->points_spent }} {{ __('loop.pts') }}
                            </p>
                        </div>
                        <p class="shrink-0 font-display text-lg font-semibold">
                            −{{ number_format($redemption->points_spent, 0) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
