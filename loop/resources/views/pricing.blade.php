<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('loop.pricing') }} · Loop</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="relative min-h-screen overflow-x-hidden">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-20 top-0 h-72 w-72 rounded-full bg-mint/25 blur-3xl"></div>
        <div class="absolute right-0 top-24 h-80 w-80 rounded-full bg-coral/15 blur-3xl"></div>
    </div>

    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('landing.business') }}" class="whitespace-nowrap text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.business') }}</a>
            <a href="{{ route('business.register') }}" class="loop-btn-mint !py-2 text-sm">{{ __('loop.cta_business') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main class="loop-shell relative z-10 py-12 sm:py-16">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-mint-deep">Loop</p>
            <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight sm:text-5xl">{{ __('loop.pricing_title') }}</h1>
            <p class="mt-4 text-base text-ink-muted sm:text-lg">{{ __('loop.pricing_blurb') }}</p>
        </div>

        <x-pricing-grid :plans="$plans" />

        <section class="mx-auto mt-16 max-w-3xl rounded-[2rem] border border-ink/10 bg-ink p-8 text-white sm:p-10">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint">{{ __('loop.why_trial_title') }}</p>
            <h2 class="mt-3 font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.why_trial_headline') }}</h2>
            <p class="mt-3 text-sm leading-relaxed text-white/70 sm:text-base">{{ __('loop.why_trial_body') }}</p>
            <a href="{{ route('business.register') }}" class="mt-6 inline-flex rounded-xl bg-mint px-5 py-3 text-sm font-semibold text-ink hover:bg-mint-deep">{{ __('loop.start_trial') }}</a>
        </section>
    </main>

    <x-site-footer />
</div>
</body>
</html>
