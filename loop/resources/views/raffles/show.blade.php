<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.raffles') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $raffle->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $raffle->prize_name }} · {{ __('loop.raffle_status_'.$raffle->status) }}</p>
            </div>
            <div class="flex gap-2">
                <x-settings-back :href="route('raffles.index')" :label="__('loop.back')" />
                @if (in_array($raffle->status, ['scheduled','live'], true) && $raffle->remainingWinnerSlots() > 0)
                    <a href="{{ route('raffles.live', $raffle) }}" class="loop-btn-mint !py-2">{{ __('loop.go_live') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="grid gap-5 sm:grid-cols-3">
        <div class="rounded-[1.5rem] bg-ink p-5 text-white">
            <p class="text-xs text-white/55">{{ __('loop.eligible') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $eligibleCount }}</p>
        </div>
        <div class="rounded-[1.5rem] bg-mint-soft p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.winners') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $raffle->winners->count() }} / {{ $raffle->winners_count }}</p>
        </div>
        <div class="rounded-[1.5rem] border border-ink/8 bg-white p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.draw_date') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $raffle->draw_at->format('d M Y') }}</p>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.claim_within', ['days' => $raffle->claim_days]) }}</p>
        </div>
    </div>

    <section class="mt-10">
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
                            #{{ $winner->draw_order }} · {{ __('loop.raffle_winner_status_'.$winner->status) }}
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
