@php
    $latest = $raffle->winners->sortByDesc('draw_order')->first();
    $justDrawn = $drawnWinnerId
        ? ($raffle->winners->firstWhere('id', $drawnWinnerId) ?? $latest)
        : $latest;
    $openWinners = $raffle->winners->filter(fn ($winner) => $winner->isOpenToClaim());
    $winnersByDate = $openWinners->groupBy(function ($winner) use ($raffle) {
        return ($winner->drawn_at ?? $raffle->drawn_at ?? now())->toDateString();
    });
    $spinMs = (int) ($spinMs ?? \App\Support\GrowthSettings::raffleSpinMs());
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.live_raffle') }}</p>
                <h1 class="mt-2 font-display text-3xl font-semibold">{{ $raffle->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.winner_of', ['n' => min($raffle->winners->count() + ($remaining > 0 ? 1 : 0), $raffle->winners_count), 'total' => $raffle->winners_count]) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('raffles.display', $raffle) }}" class="loop-btn-mint !py-2">{{ __('loop.public_display') }}</a>
                <x-back-icon :href="route('raffles.show', $raffle)" />
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]" x-data="raffleControl({
        winnerId: {{ (int) ($justDrawn?->id ?? 0) }},
        playReveal: {{ $drawnWinnerId ? 'true' : 'false' }},
        eligible: {{ (int) $eligibleCount }},
        spinMs: {{ $spinMs }},
        callingLabel: @js(__('loop.calling_our_winner')),
    })">
        <section class="rounded-[2rem] bg-ink p-6 text-white sm:p-8">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    @if ($business->logoUrl())
                        <img src="{{ $business->logoUrl() }}" alt="" class="h-12 w-12 rounded-2xl object-cover ring-2 ring-white/30">
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 font-display text-xl font-semibold ring-2 ring-white/20">{{ mb_substr($business->name, 0, 1) }}</div>
                    @endif
                    <p class="truncate text-xs font-semibold uppercase tracking-[0.16em] text-mint">{{ $business->name }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-1.5 opacity-90">
                    <x-loop-logo class="h-8 w-8" />
                    <span class="text-[11px] font-semibold uppercase tracking-[0.14em]">Loop</span>
                </div>
            </div>
            <p class="mt-6 font-display text-4xl font-semibold">{{ $raffle->prize_name }}</p>
            <p class="mt-3 text-sm text-white/70">{{ __('loop.members_are_in', ['count' => $eligibleCount]) }}</p>
            <p class="mt-8 font-display text-5xl font-semibold tabular-nums" x-show="spinning" x-text="shown" x-cloak></p>
            <template x-if="!spinning && winnerId">
                <div>
                    @if ($justDrawn)
                        <p class="text-sm uppercase tracking-[0.16em] text-mint">{{ __('loop.winner') }}</p>
                        <p class="mt-2 font-display text-4xl font-semibold">{{ $justDrawn->publicName() }}</p>
                        <p class="mt-2 text-white/60">{{ $justDrawn->publicMemberTag() }}</p>
                    @endif
                </div>
            </template>
            <p class="mt-6 font-display text-2xl font-semibold text-white/40" x-show="!spinning && !winnerId">{{ __('loop.ready_to_draw') }}</p>

            @if ($remaining > 0 && $raffle->canDrawNow())
                <form method="POST" action="{{ route('raffles.draw', $raffle) }}" class="mt-8" data-loop-no-skeleton>
                    @csrf
                    <button class="loop-btn-mint w-full text-lg" :disabled="spinning">
                        {{ $raffle->winners->isNotEmpty() ? __('loop.draw_next') : __('loop.draw_winner') }}
                    </button>
                </form>
            @elseif ($remaining > 0)
                <p class="mt-8 rounded-2xl bg-white/10 px-4 py-3 text-sm text-white/80">{{ __('loop.raffle_draw_too_soon', ['date' => $raffle->nextDrawDate()->format('j M Y')]) }}</p>
            @else
                <a href="{{ route('raffles.show', $raffle) }}" class="loop-btn-mint mt-8 inline-flex w-full justify-center">{{ __('loop.view_raffle') }}</a>
            @endif
        </section>

        @if ($openWinners->isNotEmpty())
        <section class="space-y-3">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.owner_controls') }}</p>
            @foreach ($winnersByDate as $date => $group)
                <p class="pt-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.draw_date') }} · {{ \Illuminate\Support\Carbon::parse($date)->format('j M Y') }}</p>
                @foreach ($group->sortByDesc('draw_order') as $winner)
                    <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3.5 {{ (int) $drawnWinnerId === (int) $winner->id ? 'ring-2 ring-mint' : '' }}" x-data="{ status: @js($winner->status) }">
                        <p class="font-display text-lg font-semibold">{{ $winner->displayFirstName() }} <span class="text-ink-muted">{{ $winner->displayLastBlurred() }}</span></p>
                        <p class="mt-1 text-sm font-semibold text-mint-deep">{{ $winner->customer->full_phone }}</p>
                        <p class="mt-1 text-xs text-ink-muted">
                            <span x-text="status === 'contacted' ? @js(__('loop.raffle_winner_status_contacted')) : @js(__('loop.raffle_winner_status_'.$winner->status))"></span>
                            · {{ $winner->claimHeadline() }}
                            @if ($winner->claim_by)
                                · {{ __('loop.claim_by') }} {{ $winner->claim_by->format('d M Y') }}
                            @endif
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-raffle-call :raffle="$raffle" :winner="$winner" :calling="true" />
                        </div>
                    </div>
                @endforeach
            @endforeach
            <p class="pt-2 text-xs text-ink-muted">{{ __('loop.live_raffle_privacy') }}</p>
        </section>
        @endif
    </div>
</x-app-layout>

    $justDrawn = $drawnWinnerId
        ? ($raffle->winners->firstWhere('id', $drawnWinnerId) ?? $latest)
        : $latest;
    $winnersByDate = $raffle->winners->groupBy(function ($winner) use ($raffle) {
        return ($winner->drawn_at ?? $raffle->drawn_at ?? now())->toDateString();
    });
    $spinMs = (int) ($spinMs ?? \App\Support\GrowthSettings::raffleSpinMs());
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.live_raffle') }}</p>
                <h1 class="mt-2 font-display text-3xl font-semibold">{{ $raffle->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.winner_of', ['n' => min($raffle->winners->count() + ($remaining > 0 ? 1 : 0), $raffle->winners_count), 'total' => $raffle->winners_count]) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('raffles.display', $raffle) }}" class="loop-btn-mint !py-2">{{ __('loop.public_display') }}</a>
                <x-back-icon :href="route('raffles.show', $raffle)" />
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]" x-data="raffleControl({
        winnerId: {{ (int) ($justDrawn?->id ?? 0) }},
        playReveal: {{ $drawnWinnerId ? 'true' : 'false' }},
        eligible: {{ (int) $eligibleCount }},
        spinMs: {{ $spinMs }},
        callingLabel: @js(__('loop.calling_our_winner')),
    })">
        <section class="rounded-[2rem] bg-ink p-6 text-white sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint">{{ $business->name }}</p>
            <p class="mt-4 font-display text-4xl font-semibold">{{ $raffle->prize_name }}</p>
            <p class="mt-3 text-sm text-white/70">{{ __('loop.members_are_in', ['count' => $eligibleCount]) }}</p>
            <p class="mt-8 font-display text-5xl font-semibold tabular-nums" x-show="spinning" x-text="shown" x-cloak></p>
            <template x-if="!spinning && winnerId">
                <div>
                    @if ($justDrawn)
                        <p class="text-sm uppercase tracking-[0.16em] text-mint">{{ __('loop.winner') }}</p>
                        <p class="mt-2 font-display text-4xl font-semibold">{{ $justDrawn->publicName() }}</p>
                        <p class="mt-2 text-white/60">{{ $justDrawn->publicMemberTag() }}</p>
                    @endif
                </div>
            </template>
            <p class="mt-6 font-display text-2xl font-semibold text-white/40" x-show="!spinning && !winnerId">{{ __('loop.ready_to_draw') }}</p>

            @if ($remaining > 0)
                <form method="POST" action="{{ route('raffles.draw', $raffle) }}" class="mt-8" data-loop-no-skeleton>
                    @csrf
                    <button class="loop-btn-mint w-full text-lg" :disabled="spinning">
                        {{ $raffle->winners->isNotEmpty() ? __('loop.draw_next') : __('loop.draw_winner') }}
                    </button>
                </form>
            @else
                <a href="{{ route('raffles.show', $raffle) }}" class="loop-btn-mint mt-8 inline-flex w-full justify-center">{{ __('loop.view_raffle') }}</a>
            @endif
        </section>

        @if ($raffle->winners->isNotEmpty())
        <section class="space-y-3">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.owner_controls') }}</p>
            @foreach ($winnersByDate as $date => $group)
                <p class="pt-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.draw_date') }} · {{ \Illuminate\Support\Carbon::parse($date)->format('j M Y') }}</p>
                @foreach ($group->sortByDesc('draw_order') as $winner)
                    <div class="rounded-[1.5rem] border border-ink/8 bg-white px-4 py-4 {{ (int) $drawnWinnerId === (int) $winner->id ? 'ring-2 ring-mint' : '' }}">
                        <p class="font-display text-lg font-semibold">{{ $winner->displayFirstName() }} <span class="text-ink-muted">{{ $winner->displayLastBlurred() }}</span></p>
                        <p class="mt-1 text-sm font-semibold text-mint-deep">{{ $winner->customer->full_phone }}</p>
                        <p class="mt-1 text-xs text-ink-muted">
                            {{ __('loop.raffle_winner_status_'.$winner->status) }}
                            · {{ $winner->claimHeadline() }}
                            @if ($winner->claim_by)
                                · {{ __('loop.claim_by') }} {{ $winner->claim_by->format('d M Y') }}
                            @endif
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="tel:{{ preg_replace('/\s+/', '', $winner->customer->full_phone) }}" class="loop-btn-mint !py-2 !text-sm" @click="signalCalling()">{{ __('loop.call_winner') }}</a>
                            <form method="POST" action="{{ route('raffles.contact', [$raffle, $winner]) }}">@csrf<button class="loop-btn-ghost !py-2 !text-sm">{{ __('loop.mark_contacted') }}</button></form>
                            <form method="POST" action="{{ route('raffles.claim', [$raffle, $winner]) }}">@csrf<button class="loop-btn-ghost !py-2 !text-sm">{{ __('loop.mark_claimed') }}</button></form>
                        </div>
                    </div>
                @endforeach
            @endforeach
            <p class="pt-2 text-xs text-ink-muted">{{ __('loop.live_raffle_privacy') }}</p>
        </section>
        @endif
    </div>
</x-app-layout>
