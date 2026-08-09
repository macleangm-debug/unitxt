<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.with_your_phone') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans text-ink">
<div class="relative min-h-screen overflow-x-hidden bg-chalk" x-data="loopPageMotion()">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 top-10 h-80 w-80 rounded-full bg-violet/15 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-[28rem] w-[28rem] rounded-full bg-lime/20 blur-3xl"></div>
    </div>

<x-site-header>
    <x-slot:actions>
        <a href="{{ route('staff.login') }}" class="loop-btn-ghost !py-2 text-sm">{{ __('loop.staff_login') }}</a>
        <a href="{{ route('staff.login', ['admin' => 1]) }}" class="text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.admin_login') }}</a>
    </x-slot:actions>
</x-site-header>

<main class="relative z-10">
    <section class="relative overflow-hidden">
        <div class="loop-shell relative grid items-center gap-10 py-12 lg:grid-cols-2 lg:py-16">
            <div class="animate-fade-up">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-violet">{{ __('loop.business') }}</p>
                <h1 class="mt-3 font-display text-4xl font-semibold leading-tight sm:text-5xl">Loop</h1>
                <p class="mt-2 font-display text-2xl text-ink-muted sm:text-3xl">{{ __('loop.tagline') }}</p>
                <p class="mt-5 max-w-lg text-lg text-ink-muted">{{ __('loop.business_hero_body') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('business.register') }}" class="loop-btn">{{ __('loop.cta_business') }}</a>
                    <a href="{{ route('pricing') }}" class="loop-btn-ghost">{{ __('loop.see_pricing') }}</a>
                </div>
            </div>
            <div class="loop-wallet animate-fade-up-delay p-6 sm:p-8">
                <div class="loop-orb loop-orb--a"></div>
                <div class="loop-orb loop-orb--b"></div>
                <div class="loop-orb loop-orb--c"></div>
                <div class="relative">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-lime">{{ __('loop.sale_first') }}</p>
                    <p class="mt-4 font-display text-3xl font-semibold tracking-tight text-white sm:text-4xl">{{ __('loop.with_your_phone') }}</p>
                    <p class="mt-3 max-w-sm text-sm text-white/65">{{ __('loop.sale_first_body') }}</p>
                    <div class="mt-8 grid grid-cols-3 gap-3 border-t border-white/10 pt-6 text-center">
                        <div>
                            <p class="font-display text-2xl font-semibold text-lime">1</p>
                            <p class="mt-1 text-[11px] text-white/55">{{ __('loop.shops') }}</p>
                        </div>
                        <div>
                            <p class="font-display text-2xl font-semibold text-white">14</p>
                            <p class="mt-1 text-[11px] text-white/55">{{ __('loop.pricing') }}</p>
                        </div>
                        <div>
                            <p class="font-display text-2xl font-semibold text-white">∞</p>
                            <p class="mt-1 text-[11px] text-white/55">{{ __('loop.pts') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="loop-shell grid gap-4 border-t border-ink/10 py-16 md:grid-cols-3 md:gap-5">
        @foreach ([
            ['01', 'flex_campaigns', 'flex_campaigns_body'],
            ['02', 'front_desk_ready', 'front_desk_body'],
            ['03', 'one_phone', 'one_phone_body'],
        ] as [$num, $title, $body])
            <div class="loop-glass p-6">
                <p class="font-display text-3xl font-semibold text-violet/35">{{ $num }}</p>
                <p class="mt-3 font-display text-lg font-semibold">{{ __('loop.'.$title) }}</p>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.'.$body) }}</p>
            </div>
        @endforeach
    </section>

    <section id="pricing" class="border-t border-ink/10 bg-white/35">
        <div class="loop-shell py-16">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-violet">{{ __('loop.pricing') }}</p>
                <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('loop.pricing_title') }}</h2>
                <p class="mt-3 text-ink-muted">{{ __('loop.pricing_blurb') }}</p>
            </div>
            <x-pricing-grid :plans="$plans" :cta-route="route('business.register')" :cta-label="__('loop.cta_business')" :animate="false" />
        </div>
    </section>
</main>
<x-site-footer />
<div class="loop-page-veil" :class="{ 'is-on': transitioning }" aria-hidden="true"></div>
</div>
</body>
</html>
