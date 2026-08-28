<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-back-icon :href="route('settings')" />
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.games_wins') }}</p>
                    <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.games_wins') }}</h1>
                    <p class="mt-1 text-ink-muted">{{ __('loop.games_wins_blurb') }}</p>
                </div>
            </div>
            @if (empty($platformOff) && empty($planLocked) && empty($paused))
                <a href="{{ route('games.create') }}" class="loop-btn-mint shrink-0">{{ __('loop.create_game') }}</a>
            @endif
        </div>
    </x-slot>

    @if (! empty($platformOff))
        <section class="rounded-[2rem] border border-ink/10 bg-gradient-to-br from-ink to-ink-soft p-8 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.feature_paused') }}</p>
            <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('loop.feature_paused_games_title') }}</h2>
            <p class="mt-3 max-w-xl text-sm text-white/70">{{ __('loop.feature_paused_games_body') }}</p>
        </section>
    @elseif (! empty($planLocked))
        <section class="rounded-[2rem] border border-ink/10 bg-gradient-to-br from-ink to-ink-soft p-8 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint">{{ __('loop.games_wins') }}</p>
            <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('loop.game_plan_locked_title') }}</h2>
            <p class="mt-3 max-w-xl text-sm text-white/70">{{ __('loop.game_plan_locked_body') }}</p>
            <a href="{{ route('billing.show') }}" class="loop-btn-lime mt-6 inline-flex">{{ __('loop.upgrade_now') }}</a>
        </section>
    @elseif (! empty($paused))
        <section class="rounded-[2rem] border border-ink/10 bg-gradient-to-br from-ink to-ink-soft p-8 text-white">
            <h2 class="font-display text-3xl font-semibold">{{ __('loop.game_paused') }}</h2>
            <p class="mt-3 max-w-xl text-sm text-white/70">{{ __('loop.loop_paused_safe') }}</p>
        </section>
    @endif

    @if ($games->isNotEmpty())
        <div class="mt-8 grid gap-3">
            @foreach ($games as $game)
                <a href="{{ route('games.show', $game) }}" class="loop-panel block p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ $game->typeLabel() }}</p>
                    <p class="mt-1 font-display text-xl font-semibold">{{ $game->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">
                        {{ $game->qualifyLine() }}
                        · {{ $game->starts_at->format('j M') }}–{{ $game->ends_at->format('j M') }}
                    </p>
                </a>
            @endforeach
        </div>
    @elseif (empty($platformOff) && empty($planLocked))
        <div class="mt-8 rounded-[2rem] border border-ink/8 bg-white p-8 text-center">
            <p class="font-display text-lg font-semibold">{{ __('loop.no_games_yet') }}</p>
            @if (empty($paused))
                <a href="{{ route('games.create') }}" class="loop-btn-mint mt-4 inline-flex">{{ __('loop.create_game') }}</a>
            @endif
        </div>
    @endif
</x-app-layout>
