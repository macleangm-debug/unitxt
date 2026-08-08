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
<div class="min-h-screen bg-chalk">
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('pricing') }}" class="whitespace-nowrap text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.pricing') }}</a>
        <a href="{{ route('staff.login') }}" class="loop-btn-ghost !py-2 text-sm">{{ __('loop.staff_login') }}</a>
        <a href="{{ route('business.register') }}" class="loop-btn !py-2 text-sm">{{ __('loop.get_started') }}</a>
    </x-slot:actions>
</x-site-header>

<main>
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_30%,rgba(91,46,255,0.16),transparent_40%),radial-gradient(circle_at_80%_20%,rgba(200,255,61,0.18),transparent_35%)]"></div>
        <div class="loop-shell relative grid items-center gap-10 py-12 lg:grid-cols-2 lg:py-16">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-violet">{{ __('loop.business') }}</p>
                <h1 class="mt-3 font-display text-4xl font-semibold leading-tight sm:text-5xl">Loop</h1>
                <p class="mt-2 font-display text-2xl text-ink-muted sm:text-3xl">{{ __('loop.tagline') }}</p>
                <p class="mt-5 max-w-lg text-lg text-ink-muted">{{ __('loop.business_hero_body') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('business.register') }}" class="loop-btn">{{ __('loop.cta_business') }}</a>
                    <a href="{{ route('pricing') }}" class="loop-btn-ghost">{{ __('loop.see_pricing') }}</a>
                    <a href="{{ route('staff.login') }}" class="loop-btn-ghost">{{ __('loop.staff_login') }}</a>
                </div>
                <a href="tel:{{ preg_replace('/\s+/', '', __('loop.loop_hotline_tel')) }}" class="mt-6 inline-flex items-center gap-2.5 text-sm font-semibold text-violet hover:text-ink">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-violet/10 text-violet">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                    </span>
                    <span>
                        <span class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.loop_hotline_label') }}</span>
                        <span class="underline underline-offset-2">{{ __('loop.loop_hotline_display') }}</span>
                    </span>
                </a>
            </div>
            <div class="loop-wallet p-6 sm:p-8">
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
    </section>

    <section class="loop-shell grid gap-8 border-t border-ink/10 py-16 md:grid-cols-3">
        <div>
            <p class="font-display text-3xl font-semibold text-violet/35">01</p>
            <p class="mt-3 font-display text-lg font-semibold">{{ __('loop.flex_campaigns') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.flex_campaigns_body') }}</p>
        </div>
        <div>
            <p class="font-display text-3xl font-semibold text-violet/35">02</p>
            <p class="mt-3 font-display text-lg font-semibold">{{ __('loop.front_desk_ready') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.front_desk_body') }}</p>
        </div>
        <div>
            <p class="font-display text-3xl font-semibold text-violet/35">03</p>
            <p class="mt-3 font-display text-lg font-semibold">{{ __('loop.one_phone') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('loop.one_phone_body') }}</p>
        </div>
    </section>

    <section id="pricing" class="border-t border-ink/10 bg-white">
        <div class="loop-shell py-16">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-violet">{{ __('loop.pricing') }}</p>
                <h2 class="mt-3 font-display text-3xl font-semibold">{{ __('loop.pricing_title') }}</h2>
                <p class="mt-3 text-ink-muted">{{ __('loop.pricing_blurb') }}</p>
            </div>
            <x-pricing-grid :plans="$plans" :cta-route="route('business.register')" :cta-label="__('loop.cta_business')" />
        </div>
    </section>
</main>
<x-site-footer />
</div>
</body>
</html>
