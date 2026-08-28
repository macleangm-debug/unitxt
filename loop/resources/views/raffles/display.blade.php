<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $raffle->name }} · {{ $business->name }}</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink font-sans text-white antialiased">
<div
    class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-6 py-10 text-center"
    x-data="raffleStage({
        boardUrl: @js($boardUrl),
        name: @js($raffle->name),
        prize: @js($raffle->prize_name),
        business: @js($business->name),
        eligible: {{ (int) $eligibleCount }},
        winnersCount: {{ (int) $raffle->winners_count }},
        spinMs: {{ (int) ($spinMs ?? \App\Support\GrowthSettings::raffleSpinMs()) }},
        callingLabel: @js(__('loop.calling_our_winner')),
        winnerLabel: @js(__('loop.winner')),
        membersLabel: @js(__('loop.members_are_in', ['count' => $eligibleCount])),
        drawLabel: @js(__('loop.draw_winner')),
    })"
>
    <div class="pointer-events-none absolute -left-20 top-10 h-72 w-72 rounded-full bg-mint/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-10 bottom-10 h-80 w-80 rounded-full bg-coral/20 blur-3xl"></div>

    <div class="relative flex w-full max-w-4xl items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            @if ($business->logoUrl())
                <img src="{{ $business->logoUrl() }}" alt="" class="h-14 w-14 rounded-2xl object-cover ring-2 ring-white/30">
            @else
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 font-display text-2xl font-semibold ring-2 ring-white/20">{{ mb_substr($business->name, 0, 1) }}</div>
            @endif
            <p class="relative truncate text-sm font-semibold uppercase tracking-[0.22em] text-mint" x-text="business"></p>
        </div>
        <div class="flex shrink-0 items-center gap-2 opacity-90">
            <x-loop-logo class="h-10 w-10" />
            <span class="text-xs font-semibold uppercase tracking-[0.16em]">Loop</span>
        </div>
    </div>
    <h1 class="relative mt-8 font-display text-4xl font-semibold sm:text-6xl lg:text-7xl" x-text="name"></h1>
    <p class="relative mt-4 text-lg text-white/70" x-text="membersLine()"></p>
    <p class="relative mt-1 text-sm text-white/50" x-text="winnerSlot()"></p>

    <div class="relative mt-14 min-h-[12rem]">
        <p class="font-display text-7xl font-semibold tabular-nums sm:text-8xl" x-show="phase === 'spin'" x-text="shown" x-cloak></p>
        <div x-show="phase === 'idle'" x-cloak>
            <p class="font-display text-3xl font-semibold text-white/40">{{ __('loop.ready_to_draw') }}</p>
        </div>
        <div x-show="phase === 'winner'" x-cloak>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-mint">🎉 {{ __('loop.winner') }}</p>
            <p class="mt-4 font-display text-5xl font-semibold sm:text-7xl" x-text="winnerName"></p>
            <p class="mt-3 text-xl text-white/60" x-text="winnerTag"></p>
            <p class="mt-8 font-display text-3xl font-semibold text-lime" x-text="prize"></p>
        </div>
        <div x-show="phase === 'calling'" x-cloak>
            <p class="font-display text-4xl font-semibold text-mint" x-text="callingLabel"></p>
        </div>
    </div>
</div>
</body>
</html>
