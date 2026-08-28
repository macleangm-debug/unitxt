<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.customer_landing_title') }}</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="relative min-h-screen overflow-x-hidden bg-chalk pb-24 sm:pb-0" x-data="loopPageMotion()">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 top-10 h-80 w-80 rounded-full bg-violet/15 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-[28rem] w-[28rem] rounded-full bg-lime/20 blur-3xl"></div>
    </div>

    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('stories.index') }}" class="text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.stories') }}</a>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.browse_campaigns') }}</a>
            <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-violet hover:text-ink">{{ __('loop.cta_customer') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main class="relative z-10">
        <section class="relative overflow-hidden">
            <div class="loop-shell relative grid items-center gap-10 py-12 lg:grid-cols-2 lg:py-16">
                <div class="animate-fade-up">
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-violet">{{ __('loop.customer') }}</p>
                    <h1 class="mt-3 font-display text-4xl font-semibold sm:text-5xl">Loop</h1>
                    <p class="mt-2 font-display text-2xl text-ink-muted sm:text-3xl">{{ __('loop.customer_landing_title') }}</p>
                    <p class="mt-5 max-w-lg text-lg text-ink-muted">{{ __('loop.customer_landing_wallet_body') }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('customer.login') }}" class="loop-btn">{{ __('loop.cta_customer') }}</a>
                        <a href="{{ route('discover') }}" class="loop-btn-ghost">{{ __('loop.browse_campaigns') }}</a>
                    </div>
                </div>
                <div class="loop-wallet animate-fade-up-delay p-6 sm:p-8">
                    <div class="loop-orb loop-orb--a"></div>
                    <div class="loop-orb loop-orb--b"></div>
                    <div class="loop-orb loop-orb--c"></div>
                    <div class="relative">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-lime">{{ __('loop.pts') }}</p>
                        <p class="mt-3 font-display text-5xl font-semibold text-lime">2,450</p>
                        <p class="mt-2 text-sm text-white/65">{{ __('loop.across_shops', ['count' => 8]) }}</p>
                        <div class="mt-6 grid grid-cols-2 gap-3 border-t border-white/10 pt-5 text-center sm:grid-cols-4">
                            @foreach (['flow_buy', 'flow_earn', 'flow_unlock', 'flow_enjoy'] as $key)
                                <div>
                                    <p class="text-[11px] text-white/70">{{ __('loop.'.$key) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="loop-shell grid gap-4 border-t border-ink/10 py-12 md:grid-cols-3 md:gap-5">
            @foreach ([
                ['01', 'customer_aside_1'],
                ['02', 'customer_aside_2'],
                ['03', 'customer_aside_3'],
            ] as [$num, $key])
                <div class="loop-glass p-6">
                    <p class="font-display text-3xl font-semibold text-violet/35">{{ $num }}</p>
                    <p class="mt-3 font-display text-lg font-semibold">{{ __('loop.'.$key) }}</p>
                </div>
            @endforeach
        </section>

        @include('stories.partials.landing')

        <section class="border-t border-ink/10">
            <div class="loop-shell py-12">
                <p class="font-display text-2xl font-semibold tracking-tight">{{ __('loop.help_faqs') }}</p>
                <p class="mt-2 max-w-lg text-sm text-ink-muted">{{ __('loop.help_landing_cta') }}</p>
                <a href="{{ route('help') }}" class="loop-btn mt-5 inline-flex">{{ __('loop.help_faqs') }}</a>
            </div>
        </section>
    </main>

    <div class="loop-sticky-cta sm:hidden">
        <a href="{{ route('customer.login') }}" class="loop-btn w-full justify-center">{{ __('loop.cta_customer') }}</a>
    </div>

    <x-site-footer />
    <div class="loop-page-veil" :class="{ 'is-on': transitioning }" aria-hidden="true"></div>
</div>
<x-page-skeleton variant="public" />
</body>
</html>
