<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loop — Loyalty without the card</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="relative min-h-screen overflow-hidden">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-20 top-16 h-72 w-72 rounded-full bg-mint/25 blur-3xl animate-float"></div>
        <div class="absolute right-0 top-0 h-[26rem] w-[26rem] rounded-full bg-coral/15 blur-3xl"></div>
    </div>

    <header class="loop-shell relative z-10 flex items-center justify-between py-6">
        <div class="flex items-center gap-3">
            <x-loop-logo class="h-11 w-11" />
            <span class="font-display text-3xl font-semibold tracking-tight">Loop</span>
        </div>
        <a href="{{ route('discover') }}" class="text-sm font-semibold text-ink-muted hover:text-ink">Browse campaigns</a>
    </header>

    <main class="loop-shell relative z-10 grid min-h-[78vh] items-center gap-10 py-10 lg:grid-cols-2">
        <div class="animate-fade-up">
            <p class="font-display text-6xl font-semibold tracking-tight sm:text-7xl">Loop</p>
            <p class="mt-3 font-display text-2xl text-ink-soft sm:text-3xl">Loyalty without the card.</p>
            <p class="mt-5 max-w-md text-lg leading-relaxed text-ink-muted">
                Helping Tanzanian shops bring customers back — with points on every purchase, and rewards applied at the till.
            </p>
        </div>

        <div class="grid gap-4 animate-fade-up-delay">
            <a href="{{ route('landing.business') }}" class="loop-panel group block p-7 transition hover:-translate-y-0.5 hover:bg-white">
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-mint-deep">Business</p>
                <h2 class="mt-2 font-display text-2xl font-semibold">I’m a business owner</h2>
                <p class="mt-2 text-ink-muted">Set up shops, launch campaigns, and run the till — in store or on phone orders.</p>
                <span class="mt-5 inline-flex text-sm font-semibold text-ink group-hover:text-mint-deep">Continue →</span>
            </a>

            <a href="{{ route('landing.customer') }}" class="loop-panel group block p-7 transition hover:-translate-y-0.5 hover:bg-white">
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-coral">Customer</p>
                <h2 class="mt-2 font-display text-2xl font-semibold">I’m a customer</h2>
                <p class="mt-2 text-ink-muted">Enter your phone number to see points across coffee, fashion, food, and more.</p>
                <span class="mt-5 inline-flex text-sm font-semibold text-ink group-hover:text-coral">Continue →</span>
            </a>
        </div>
    </main>
</div>
</body>
</html>
