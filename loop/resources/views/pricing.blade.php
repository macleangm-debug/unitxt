<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('loop.pricing') }} · Loop</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="relative min-h-screen overflow-x-hidden bg-chalk" x-data="loopPageMotion()">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-20 top-0 h-72 w-72 rounded-full bg-violet/20 blur-3xl"></div>
        <div class="absolute right-0 top-24 h-80 w-80 rounded-full bg-lime/20 blur-3xl"></div>
    </div>

    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('landing.business') }}" class="whitespace-nowrap text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.business') }}</a>
            <a href="{{ route('business.register') }}" class="loop-btn !py-2 text-sm">{{ __('loop.cta_business') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main class="loop-shell relative z-10 py-12 sm:py-16">
        <div class="mx-auto max-w-2xl text-center animate-fade-up">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet">Loop</p>
            <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight sm:text-5xl">{{ __('loop.pricing_title') }}</h1>
            <p class="mt-4 text-base text-ink-muted sm:text-lg">{{ $pricingBlurb }}</p>
        </div>

        <div class="animate-fade-up-delay">
            <x-pricing-grid :plans="$plans" :animate="false" />
        </div>

        <section class="loop-wallet mx-auto mt-16 max-w-3xl p-8 sm:p-10">
            <div class="loop-orb loop-orb--a"></div>
            <div class="loop-orb loop-orb--b"></div>
            <div class="loop-orb loop-orb--c"></div>
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.why_trial_title') }}</p>
                <h2 class="mt-3 font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.why_trial_headline') }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-white/70 sm:text-base">{{ __('loop.why_trial_body') }}</p>
                <a href="{{ route('business.register') }}" class="loop-btn-lime mt-6">{{ __('loop.start_trial') }}</a>
            </div>
        </section>
    </main>

    <x-site-footer />
    <div class="loop-page-veil" :class="{ 'is-on': transitioning }" aria-hidden="true"></div>
</div>
<x-page-skeleton variant="public" />
</body>
</html>
