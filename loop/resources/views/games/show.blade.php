<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-back-icon :href="route('games.index')" />
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ $game->typeLabel() }}</p>
                    <h1 class="mt-1 font-display text-3xl font-semibold">{{ $game->name }}</h1>
                    <p class="mt-1 text-ink-muted">{{ $game->qualifyLine() }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    @if (! empty($paused))
        <p class="mb-6 rounded-2xl bg-ink px-4 py-3 text-sm text-white">{{ __('loop.game_paused') }}</p>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-[1.75rem] bg-ink p-5 text-white">
            <p class="text-xs uppercase tracking-[0.14em] text-white/55">{{ __('loop.game_stat_played') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['played']) }}</p>
        </div>
        <div class="rounded-[1.75rem] border border-ink/8 bg-white p-5">
            <p class="text-xs uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.game_stat_won') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['won']) }}</p>
        </div>
        <div class="rounded-[1.75rem] border border-ink/8 bg-white p-5">
            <p class="text-xs uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.game_stat_claimed') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['claimed']) }}</p>
        </div>
        <div class="rounded-[1.75rem] bg-mint-soft p-5">
            <p class="text-xs uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.game_stat_sales') }}</p>
            <p class="mt-2 font-display text-2xl font-semibold">{{ $business->currency }} {{ number_format($stats['qualifying_sales']) }}</p>
        </div>
    </div>

    <section class="mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.game_what_they_win') }}</h2>
        <div class="mt-4 space-y-3">
            @foreach ($game->prizes as $prize)
                <div class="flex items-center justify-between rounded-[1.5rem] border border-ink/8 bg-white px-5 py-4">
                    <p class="font-semibold">{{ $prize->name }}</p>
                    <p class="text-sm text-ink-muted">{{ $prize->awarded_count }} / {{ $prize->quantity }}</p>
                </div>
            @endforeach
            <p class="text-sm text-ink-muted">{{ __('loop.game_no_prize_also') }}</p>
        </div>
    </section>
</x-app-layout>
