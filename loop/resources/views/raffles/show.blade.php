@php
    $canDraw = in_array($raffle->status, ['scheduled', 'live'], true) && $raffle->remainingWinnerSlots() > 0;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.raffles') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $raffle->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.raffle_status_'.$raffle->status) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-back-icon :href="route('raffles.index')" />
                @if (\App\Support\FeatureFlags::enabled('content_studio'))
                <a href="{{ route('content-studio.index', ['about' => 'raffle', 'id' => $raffle->id]) }}" class="loop-btn-ghost !py-2">{{ __('loop.share_raffle') }}</a>
                @endif
                @if ($canDraw && \App\Support\FeatureFlags::enabled('raffles'))
                    <a href="{{ route('raffles.live', $raffle) }}" class="loop-btn-mint !py-2">{{ __('loop.start_live_draw') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-[1.75rem] bg-ink p-6 text-white sm:col-span-2 lg:col-span-1">
            <p class="text-xs uppercase tracking-[0.14em] text-white/55">{{ __('loop.whats_being_won') }}</p>
            <p class="mt-3 font-display text-3xl font-semibold">{{ $raffle->prize_name }}</p>
        </div>
        <div class="rounded-[1.75rem] bg-mint-soft p-6">
            <p class="text-xs uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.how_many_are_in') }}</p>
            <p class="mt-3 font-display text-3xl font-semibold">{{ __('loop.members_are_in', ['count' => $eligibleCount]) }}</p>
        </div>
        <div class="rounded-[1.75rem] border border-ink/8 bg-white p-6">
            <p class="text-xs uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.how_many_winners') }}</p>
            <p class="mt-3 font-display text-3xl font-semibold">{{ $raffle->winners->count() }} / {{ $raffle->winners_count }}</p>
        </div>
        <div class="rounded-[1.75rem] border border-ink/8 bg-white p-6">
            <p class="text-xs uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.when_is_the_draw') }}</p>
            <p class="mt-3 font-display text-2xl font-semibold">{{ $raffle->draw_at->format('l j M') }}</p>
        </div>
        <div class="rounded-[1.75rem] border border-ink/8 bg-white p-6 sm:col-span-2 lg:col-span-2">
            <p class="text-xs uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.how_long_to_claim') }}</p>
            <p class="mt-3 font-display text-2xl font-semibold">{{ __('loop.claim_within', ['days' => $raffle->claim_days]) }}</p>
        </div>
    </div>

    @if ($canDraw)
        <div class="mt-8">
            <a href="{{ route('raffles.live', $raffle) }}" class="loop-btn-mint inline-flex min-h-[3.5rem] px-8 text-lg">{{ __('loop.start_live_draw') }}</a>
        </div>
    @endif

    <section class="mt-12">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.winners') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($raffle->winners as $winner)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4">
                    <div>
                        <p class="font-display text-lg font-semibold">
                            {{ $winner->displayFirstName() }}
                            <span class="text-ink-muted">{{ $winner->displayLastBlurred() }}</span>
                        </p>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ __('loop.winner_of', ['n' => $winner->draw_order, 'total' => $raffle->winners_count]) }}
                            · {{ __('loop.raffle_winner_status_'.$winner->status) }}
                            · {{ $winner->claimHeadline() }}
                            @if ($winner->claim_by)
                                · {{ __('loop.claim_by') }} {{ $winner->claim_by->format('d M Y') }}
                            @endif
                        </p>
                        <p class="mt-1 text-sm font-semibold text-mint-deep">{{ $winner->customer->full_phone }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="tel:{{ preg_replace('/\s+/', '', $winner->customer->full_phone) }}" class="loop-btn-mint !py-2 !text-sm">{{ __('loop.call_winner') }}</a>
                        <form method="POST" action="{{ route('raffles.contact', [$raffle, $winner]) }}">@csrf<button class="loop-btn-ghost !py-2 !text-sm">{{ __('loop.mark_contacted') }}</button></form>
                        <form method="POST" action="{{ route('raffles.claim', [$raffle, $winner]) }}">@csrf<button class="loop-btn-ghost !py-2 !text-sm">{{ __('loop.mark_claimed') }}</button></form>
                    </div>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_winners_yet') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
