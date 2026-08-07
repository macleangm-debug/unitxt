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
        @if (session('status') && ! session('all_set') && ! session('confirm'))
            <div class="mb-6 rounded-xl border border-mint/40 bg-mint-soft px-4 py-3 text-sm text-ink">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </main>
</div>

@php
    $confirm = session('confirm');
    if (session('all_set')) {
        $confirm = [
            'title' => __('loop.all_set_title'),
            'body' => __('loop.all_set_body'),
            'cta' => __('loop.start_selling'),
            'url' => route('till.index'),
            'celebrate' => (bool) \App\Support\GrowthSettings::settings()['onboarding_celebrate'],
        ];
    }
@endphp

@if ($confirm)
    <div
        x-data="{ open: true }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center px-4"
        @keydown.escape.window="open=false"
    >
        <div class="absolute inset-0 bg-ink/55 backdrop-blur-sm" @click="open=false"></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-white/10 bg-white p-8 text-center shadow-[0_40px_100px_rgba(11,31,42,0.35)]">
            @if (!empty($confirm['celebrate']))
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    @foreach (range(1,36) as $i)
                        <span class="absolute opacity-90"
                              style="left: {{ rand(2,96) }}%; top: -12%; width: {{ rand(6,12) }}px; height: {{ rand(8,16) }}px; border-radius: {{ $i % 3 === 0 ? '999px' : '2px' }}; background: {{ ['#2DD4A8','#FF6B4A','#0B1F2A','#F4C95F','#7DD3C0'][$i % 5] }}; animation: loop-confetti {{ 1.6 + ($i % 5) * 0.18 }}s ease-in {{ $i * 0.04 }}s infinite;"></span>
                    @endforeach
                </div>
                <style>
                    @keyframes loop-confetti {
                        0% { transform: translate3d(0,-10%,0) rotate(0deg); opacity: 0; }
                        12% { opacity: 1; }
                        100% { transform: translate3d({{ rand(-40,40) }}px, 120vh, 0) rotate({{ rand(180,720) }}deg); opacity: 0; }
                    }
                </style>
            @endif
            <div class="relative">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-mint to-mint-deep text-3xl text-ink shadow-[0_12px_40px_rgba(45,212,168,0.35)]">✓</div>
                <p class="mt-5 font-display text-3xl font-semibold tracking-tight">{{ $confirm['title'] }}</p>
                <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ $confirm['body'] }}</p>
                <a href="{{ $confirm['url'] }}" class="loop-btn-mint mt-7 inline-flex w-full">{{ $confirm['cta'] }}</a>
                <button type="button" class="mt-3 text-sm font-semibold text-ink-muted hover:text-ink" @click="open=false">{{ __('loop.done') }}</button>
            </div>
        </div>
    </div>
@endif
</body>
</html>
