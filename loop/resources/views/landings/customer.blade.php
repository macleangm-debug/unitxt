<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.customer_landing_title') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="relative min-h-screen overflow-x-hidden">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-20 top-10 h-72 w-72 rounded-full bg-mint/25 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-80 w-80 rounded-full bg-coral/15 blur-3xl"></div>
    </div>

    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.browse_campaigns') }}</a>
            <a href="{{ route('home') }}" class="text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.home') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main class="loop-shell relative z-10 py-12 sm:py-16">
        <div class="mx-auto max-w-2xl text-center">
            <x-loop-logo class="mx-auto h-14 w-14" />
            <p class="mt-6 text-xs font-semibold uppercase tracking-[0.18em] text-mint-deep">Loop</p>
            <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight sm:text-5xl">{{ __('loop.customer_landing_title') }}</h1>
            <p class="mt-4 text-base leading-relaxed text-ink-muted sm:text-lg">{{ __('loop.customer_landing_body') }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('customer.login') }}" class="loop-btn-mint">{{ __('loop.cta_customer') }}</a>
                <a href="{{ route('discover') }}" class="loop-btn-ghost">{{ __('loop.browse_campaigns') }}</a>
            </div>
        </div>

        <div class="mx-auto mt-14 grid max-w-3xl gap-6 sm:grid-cols-3">
            @foreach (['customer_aside_1', 'customer_aside_2', 'customer_aside_3'] as $point)
                <div class="rounded-2xl border border-ink/8 bg-white/80 px-5 py-4 text-left">
                    <p class="text-sm font-semibold text-ink">◆ {{ __('loop.'.$point) }}</p>
                </div>
            @endforeach
        </div>
    </main>

    <x-site-footer />
</div>
</body>
</html>
