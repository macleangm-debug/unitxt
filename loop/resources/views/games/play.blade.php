@php
    $played = ! $play->isPending();
    $slices = $game->prizes->pluck('name')->push(__('loop.game_better_luck_slice'))->values();
    $landing = $play->isWin()
        ? max(0, $game->prizes->search(fn ($p) => (int) $p->id === (int) $play->prize_id))
        : max(0, $slices->count() - 1);
@endphp
<x-app-layout>
    <div
        class="mx-auto max-w-lg"
        x-data="gamePlay({
            type: @js($game->type),
            played: {{ $played ? 'true' : 'false' }},
            justPlayed: {{ ! empty($justPlayed) ? 'true' : 'false' }},
            revealMs: {{ (int) $revealMs }},
            landing: {{ (int) $landing }},
            sliceCount: {{ $slices->count() }},
            win: {{ $play->isWin() ? 'true' : 'false' }},
        })"
    >
        <p class="text-center text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ $business->name }}</p>
        <h1 class="mt-2 text-center font-display text-3xl font-semibold">{{ $game->typeLabel() }}</h1>
        <p class="mt-2 text-center text-sm text-ink-muted">{{ $game->qualifyLine() }}</p>

        <div class="mt-8">
            @if ($game->type === 'spin')
                <div class="loop-game-wheel mx-auto" :class="{ 'is-spinning': spinning }" :style="wheelStyle()">
                    @foreach ($slices as $i => $label)
                        <span class="loop-game-slice" style="--i: {{ $i }}; --n: {{ $slices->count() }}">{{ $label }}</span>
                    @endforeach
                </div>
            @elseif ($game->type === 'boxes')
                <div class="grid grid-cols-3 gap-3">
                    @foreach (range(0, 5) as $i)
                        <button type="button" class="loop-game-box" :class="{ 'is-open': spinning || played }" @click="choose({{ $i }})" :disabled="spinning || played">
                            <span x-show="!played && !spinning">🎁</span>
                            <span x-show="played || spinning" x-cloak>{{ $i === 0 || $played ? ($play->isWin() ? $play->prize?->name : __('loop.game_better_luck_slice')) : '·' }}</span>
                        </button>
                    @endforeach
                </div>
            @else
                <button type="button" class="loop-game-scratch" :class="{ 'is-revealed': spinning || played }" @click="choose(0)" :disabled="spinning || played">
                    <span class="loop-game-scratch-cover" x-show="!played && !spinning">{{ __('loop.game_scratch_cta') }}</span>
                    <span class="loop-game-scratch-result">{{ $play->isWin() ? $play->prize?->name : __('loop.game_better_luck_title') }}</span>
                </button>
            @endif
        </div>

        @if ($play->isPending())
            <form method="POST" action="{{ route('games.reveal', $play) }}" class="mt-8" x-ref="form" data-loop-no-skeleton>
                @csrf
                <button class="loop-btn-mint w-full text-lg" :disabled="spinning">{{ __('loop.game_play_cta_'.$game->type) }}</button>
            </form>
        @elseif ($play->isWin() && $play->status !== 'claimed')
            <div class="mt-8 text-center">
                <p class="font-display text-2xl font-semibold">{{ $play->prize?->name }}</p>
                <form method="POST" action="{{ route('games.claim', $play) }}" class="mt-4">
                    @csrf
                    <button class="loop-btn-mint w-full">{{ __('loop.game_claim_now') }}</button>
                </form>
            </div>
        @elseif ($play->status === 'claimed')
            <p class="mt-8 text-center font-semibold text-mint-deep">{{ __('loop.game_claimed_title') }}</p>
        @else
            <p class="mt-8 text-center text-ink-muted">{{ __('loop.game_better_luck_body') }}</p>
        @endif
    </div>
</x-app-layout>
