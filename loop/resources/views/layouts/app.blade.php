<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Loop') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans">
<div class="min-h-screen">
    @include('layouts.navigation')
    @isset($header)
        <header class="loop-shell pt-8 pb-2"><div class="animate-fade-up">{{ $header }}</div></header>
    @endisset
    <main class="loop-shell py-6 pb-16">
        @if (session('status') && ! session('all_set'))
            <div class="mb-6 rounded-xl border border-mint/40 bg-mint-soft px-4 py-3 text-sm text-ink">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </main>
</div>

@if (session('all_set'))
    <div
        x-data="{ open: true }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center px-4"
    >
        <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm" @click="open=false"></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-[2rem] bg-white p-8 text-center shadow-[0_30px_80px_rgba(11,31,42,0.25)]">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                @foreach (range(1,18) as $i)
                    <span class="absolute animate-bounce rounded-sm opacity-80"
                          style="left: {{ rand(5,90) }}%; top: {{ rand(-10,40) }}%; width: {{ rand(6,10) }}px; height: {{ rand(8,14) }}px; background: {{ ['#2DD4A8','#FF6B4A','#0B1F2A','#F4C95F'][array_rand(['#2DD4A8','#FF6B4A','#0B1F2A','#F4C95F'])] }}; animation-delay: {{ $i * 0.05 }}s;"></span>
                @endforeach
            </div>
            <div class="relative">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-mint text-3xl text-ink">✓</div>
                <p class="mt-5 font-display text-3xl font-semibold">{{ __('loop.all_set_title') }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.all_set_body') }}</p>
                <a href="{{ route('till.index') }}" class="loop-btn-mint mt-6 inline-flex w-full">{{ __('loop.start_selling') }}</a>
                <button type="button" class="mt-3 text-sm font-semibold text-ink-muted" @click="open=false">{{ __('loop.done') }}</button>
            </div>
        </div>
    </div>
@endif
</body>
</html>
