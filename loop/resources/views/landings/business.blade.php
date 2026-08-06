<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.with_your_phone') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('staff.login') }}" class="loop-btn-ghost !py-2 text-sm">{{ __('loop.staff_login') }}</a>
        <a href="{{ route('business.register') }}" class="loop-btn !py-2 text-sm">{{ __('loop.get_started') }}</a>
    </x-slot:actions>
</x-site-header>

<main>
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_30%,rgba(45,212,168,0.22),transparent_40%),radial-gradient(circle_at_80%_20%,rgba(255,107,74,0.16),transparent_35%)]"></div>
        <div class="loop-shell relative grid items-center gap-10 py-12 lg:grid-cols-2">
            <div class="order-2 lg:order-1">
                <div class="overflow-hidden rounded-[2rem] bg-ink shadow-[0_30px_80px_rgba(11,31,42,0.18)]">
                    <img src="https://images.unsplash.com/photo-1556740738-b6a63e27c4df?auto=format&fit=crop&w=1400&q=80" alt="" class="h-72 w-full object-cover opacity-90 sm:h-[22rem]">
                    <div class="space-y-2 p-6 text-white">
                        <p class="font-display text-xl font-semibold">{{ __('loop.sale_first') }}</p>
                        <p class="text-sm text-white/70">{{ __('loop.sale_first_body') }}</p>
                    </div>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.business') }}</p>
                <h1 class="mt-3 font-display text-4xl font-semibold leading-tight sm:text-5xl">Loop</h1>
                <p class="mt-2 font-display text-2xl text-ink-muted sm:text-3xl">{{ __('loop.tagline') }}</p>
                <p class="mt-5 max-w-lg text-lg text-ink-muted">{{ __('loop.business_hero_body') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('business.register') }}" class="loop-btn-mint">{{ __('loop.cta_business') }}</a>
                    <a href="{{ route('staff.login') }}" class="loop-btn-ghost">{{ __('loop.staff_login') }}</a>
                </div>
            </div>
        </div>
    </section>

    <section class="loop-shell grid gap-6 border-t border-ink/10 py-16 md:grid-cols-3">
        <div class="rounded-3xl border border-ink/10 bg-white/70 p-6 backdrop-blur-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-mint/40 to-coral/25 font-display text-lg">◎</div>
            <p class="mt-4 font-display text-lg font-semibold">{{ __('loop.flex_campaigns') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.flex_campaigns_body') }}</p>
        </div>
        <div class="rounded-3xl border border-ink/10 bg-white/70 p-6 backdrop-blur-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-mint/40 to-coral/25 font-display text-lg">▣</div>
            <p class="mt-4 font-display text-lg font-semibold">{{ __('loop.front_desk_ready') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.front_desk_body') }}</p>
        </div>
        <div class="rounded-3xl border border-ink/10 bg-white/70 p-6 backdrop-blur-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-mint/40 to-coral/25 font-display text-lg">◉</div>
            <p class="mt-4 font-display text-lg font-semibold">{{ __('loop.one_phone') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.one_phone_body') }}</p>
        </div>
    </section>
</main>
<x-site-footer />
</body>
</html>
