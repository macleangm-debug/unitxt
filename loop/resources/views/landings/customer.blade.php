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
<div class="relative min-h-screen overflow-x-hidden bg-chalk" x-data="loopPageMotion()">
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
        <section class="loop-shell grid items-center gap-10 py-10 sm:py-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-14 lg:py-16">
            <div class="animate-fade-up">
                <p class="font-display text-[clamp(3rem,11vw,5.5rem)] font-semibold leading-[0.92] tracking-tight text-ink">Loop</p>
                <h1 class="mt-5 max-w-xl font-display text-[clamp(1.4rem,4vw,2.15rem)] font-semibold leading-snug text-ink">
                    {{ __('loop.customer_landing_title') }}
                </h1>
                <p class="mt-4 max-w-md text-base leading-relaxed text-ink-muted sm:text-lg">
                    {{ __('loop.customer_landing_wallet_body') }}
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ route('customer.login') }}" class="loop-btn w-full justify-center sm:w-auto">
                        {{ __('loop.start_using_loop') }}
                    </a>
                    <a href="{{ route('discover') }}" class="inline-flex items-center justify-center px-1 py-2 text-sm font-semibold text-ink-muted underline-offset-4 hover:text-ink hover:underline sm:px-3">
                        {{ __('loop.browse_campaigns') }} →
                    </a>
                </div>

                <div class="mt-10 grid max-w-md grid-cols-3 gap-3 text-center sm:gap-4">
                    @foreach ([
                        ['01', 'customer_flow_phone'],
                        ['02', 'customer_flow_points'],
                        ['03', 'customer_flow_rewards'],
                    ] as [$num, $key])
                        <div class="loop-glass px-2 py-3">
                            <p class="font-display text-2xl font-semibold text-violet">{{ $num }}</p>
                            <p class="mt-1 text-xs font-semibold leading-snug text-ink sm:text-sm">{{ __('loop.'.$key) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-[22rem] animate-fade-up-delay lg:mx-0 lg:justify-self-end">
                <div class="absolute -left-8 top-10 h-40 w-40 rounded-full bg-violet/20 blur-3xl"></div>
                <div class="absolute -right-6 bottom-8 h-36 w-36 rounded-full bg-lime/30 blur-3xl"></div>

                <div class="loop-glass relative !rounded-[2rem] p-3">
                    <div class="overflow-hidden rounded-[1.55rem] bg-chalk/80">
                        <div class="flex items-center justify-between px-4 py-3">
                            <div class="flex items-center gap-2">
                                <x-loop-logo class="h-7 w-7" />
                                <span class="font-display text-sm font-semibold">Loop</span>
                            </div>
                            <span class="text-[10px] font-semibold text-ink-muted">9:41</span>
                        </div>

                        <div class="loop-wallet mx-3 mb-3 rounded-[1.25rem] px-4 py-5">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-lime">Loop</p>
                            <p class="mt-5 font-display text-5xl font-semibold leading-none text-lime">2,450</p>
                            <p class="mt-2 text-[11px] font-medium uppercase tracking-[0.14em] text-white/50">{{ __('loop.pts') }}</p>
                            <p class="mt-2 text-sm text-white/70">{{ __('loop.across_shops', ['count' => 8]) }}</p>
                            <div class="mt-5 rounded-xl bg-lime px-3 py-2.5 text-center text-sm font-semibold text-ink">
                                {{ __('loop.see_rewards') }}
                            </div>
                        </div>

                        <div class="space-y-3 px-4 pb-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.ready_to_redeem') }}</p>
                            <div class="rounded-2xl bg-violet/90 px-3.5 py-3 text-white backdrop-blur-md">
                                <p class="text-xs text-white/70">Harbor Beans</p>
                                <div class="mt-1 flex items-end justify-between gap-2">
                                    <p class="font-display text-base font-semibold">5% off anything</p>
                                    <span class="rounded-lg bg-lime px-2 py-1 text-[11px] font-semibold text-ink">{{ __('loop.redeem') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-ink/8 bg-white/40">
            <div class="loop-shell py-12 sm:py-16">
                <p class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">{{ __('loop.phone_points_loop') }}</p>
                <p class="mt-3 max-w-xl text-sm text-ink-muted sm:text-base">{{ __('loop.phone_points_loop_body') }}</p>
                <div class="mt-8 grid gap-4 sm:grid-cols-3 sm:gap-5">
                    @foreach ([
                        ['customer_aside_1', '01'],
                        ['customer_aside_2', '02'],
                        ['customer_aside_3', '03'],
                    ] as [$key, $num])
                        <div class="loop-glass p-6">
                            <p class="font-display text-3xl font-semibold text-violet/40">{{ $num }}</p>
                            <p class="mt-2 font-display text-lg font-semibold leading-snug">{{ __('loop.'.$key) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        @include('stories.partials.landing')
    </main>

    <x-site-footer />
    <div class="loop-page-veil" :class="{ 'is-on': transitioning }" aria-hidden="true"></div>
</div>
<x-page-skeleton variant="public" />
</body>
</html>
