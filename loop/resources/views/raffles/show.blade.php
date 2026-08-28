@php
    $canDraw = \App\Support\FeatureFlags::enabled('raffles') && $raffle->canDrawNow();
    $openWinners = $raffle->winners->filter(fn ($winner) => $winner->isOpenToClaim());
    $claimedCount = $raffle->winners->where('status', 'claimed')->count();
    $winnersByDate = $openWinners->groupBy(function ($winner) use ($raffle) {
        return ($winner->drawn_at ?? $raffle->drawn_at ?? now())->toDateString();
    });
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.raffles') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $raffle->name }}</h1>
                    @if ($raffle->status === 'live')
                        <x-status-pill :live="true" size="lg" />
                    @else
                        <span class="rounded-full bg-chalk px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.raffle_status_'.$raffle->status) }}</span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-ink-muted">{{ $raffle->prize_name }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-back-icon :href="route('raffles.index')" />
                @if (\App\Support\FeatureFlags::enabled('content_studio'))
                    <a href="{{ route('content-studio.index', ['about' => 'raffle', 'id' => $raffle->id]) }}" class="loop-btn-ghost !py-2.5">{{ __('loop.share_raffle') }}</a>
                @endif
                @if ($canDraw)
                    <a href="{{ route('raffles.live', $raffle) }}" class="loop-btn-mint !py-2.5">{{ __('loop.start_live_draw') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="mt-6 grid grid-cols-3 gap-3">
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.whats_being_won') }}</p>
            <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ $raffle->prize_name }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.how_many_are_in') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $eligibleCount }}</p>
            <p class="mt-0.5 text-[10px] text-ink-muted">{{ __('loop.members') }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.how_many_winners') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold sm:text-3xl">{{ $raffle->winners->count() }} / {{ $raffle->winners_count }}</p>
        </div>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3">
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.when_is_the_draw') }}</p>
            <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ $raffle->nextDrawDate()->format('l j M') }}</p>
        </div>
        <div class="rounded-2xl border border-ink/10 bg-white p-4 sm:p-5">
            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.how_long_to_claim') }}</p>
            <p class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ __('loop.claim_within', ['days' => $raffle->claim_days]) }}</p>
        </div>
    </div>

    @if ($openWinners->isNotEmpty())
    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold">{{ __('loop.winners') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.raffle_open_winners_blurb') }}</p>
            </div>
        </div>
        <div class="space-y-6">
            @foreach ($winnersByDate as $date => $group)
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.draw_date') }} · {{ \Illuminate\Support\Carbon::parse($date)->format('l j M Y') }}</p>
                    <div class="mt-3 space-y-3">
                        @foreach ($group as $winner)
                            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-ink/10 bg-white px-4 py-3.5" x-data="{ status: @js($winner->status) }">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">
                                        {{ $winner->displayFirstName() }}
                                        <span class="text-ink-muted">{{ $winner->displayLastBlurred() }}</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-ink-muted">
                                        {{ __('loop.winner_of', ['n' => $winner->draw_order, 'total' => $raffle->winners_count]) }}
                                        · <span x-text="status === 'contacted' ? @js(__('loop.raffle_winner_status_contacted')) : @js(__('loop.raffle_winner_status_'.$winner->status))"></span>
                                        · {{ $winner->claimHeadline() }}
                                        @if ($winner->claim_by)
                                            · {{ __('loop.claim_by') }} {{ $winner->claim_by->format('d M Y') }}
                                        @endif
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-mint-deep">{{ $winner->customer->full_phone }}</p>
                                </div>
                                <x-raffle-call :raffle="$raffle" :winner="$winner" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @elseif ($claimedCount > 0)
        <section class="mt-8">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.winners') }}</h2>
            <p class="mt-2 text-sm text-ink-muted">{{ trans_choice('loop.raffle_claimed_in_sales', $claimedCount, ['count' => $claimedCount]) }}</p>
            <a href="{{ route('transactions.index') }}" class="mt-3 inline-flex text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }} →</a>
        </section>
    @endif
</x-app-layout>
