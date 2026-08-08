<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.tagline') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="relative min-h-screen overflow-x-hidden bg-chalk">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 top-10 h-80 w-80 rounded-full bg-violet/15 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-[28rem] w-[28rem] rounded-full bg-lime/20 blur-3xl"></div>
    </div>

    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('affiliates.landing') }}" class="whitespace-nowrap text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.affiliates') }}</a>
            <a href="{{ route('discover') }}" class="whitespace-nowrap text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.browse_campaigns') }}</a>
            <a href="{{ route('landing.business') }}" class="whitespace-nowrap text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.business') }}</a>
            <a href="{{ route('landing.customer') }}" class="whitespace-nowrap text-sm font-semibold text-violet hover:text-ink">{{ __('loop.customer') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main class="loop-shell relative z-10 grid items-center gap-8 py-10 lg:min-h-[70vh] lg:grid-cols-2 lg:gap-14 lg:py-16">
        <div class="animate-fade-up">
            <p class="font-display text-5xl font-semibold tracking-tight sm:text-6xl lg:text-7xl">Loop</p>
            <p class="mt-3 font-display text-2xl text-ink-soft sm:text-3xl">{{ __('loop.tagline') }}</p>
            <p class="mt-4 max-w-md text-base leading-relaxed text-ink-muted sm:text-lg">{{ __('loop.hero_body') }}</p>
            <p class="mt-6 text-sm font-semibold text-violet">{{ __('loop.phone_points_loop') }}</p>
        </div>

        <div class="grid gap-4 animate-fade-up-delay">
            <a href="{{ route('landing.business') }}" class="group relative overflow-hidden rounded-[1.75rem] bg-ink p-6 text-white transition hover:-translate-y-0.5 sm:p-8">
                <div class="pointer-events-none absolute -right-8 -top-8 h-36 w-36 rounded-full bg-violet/40 blur-2xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-lime">{{ __('loop.business') }}</p>
                <h2 class="mt-3 font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.im_business') }}</h2>
                <p class="mt-3 max-w-sm text-sm text-white/70 sm:text-base">{{ __('loop.business_blurb') }}</p>
                <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-lime">{{ __('loop.continue') }} <span aria-hidden="true">→</span></span>
            </a>

            <a href="{{ route('landing.customer') }}" class="group relative overflow-hidden rounded-[1.75rem] border border-ink/10 bg-white p-6 transition hover:-translate-y-0.5 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet">{{ __('loop.customer') }}</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-ink sm:text-3xl">{{ __('loop.im_customer') }}</h2>
                <p class="mt-3 max-w-sm text-sm text-ink-muted sm:text-base">{{ __('loop.customer_blurb') }}</p>
                <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-violet">{{ __('loop.continue') }} <span aria-hidden="true">→</span></span>
            </a>
        </div>
    </main>

    <section id="how" class="relative z-10 border-t border-ink/10 bg-white">
        <div class="loop-shell py-14 sm:py-20">
            <h2 class="font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.how_title') }}</h2>
            <div class="mt-10 grid gap-8 sm:grid-cols-3">
                @foreach ([
                    ['01', 'how_1_title', 'how_1_body'],
                    ['02', 'how_2_title', 'how_2_body'],
                    ['03', 'how_3_title', 'how_3_body'],
                ] as [$num, $title, $body])
                    <div>
                        <p class="font-display text-4xl font-semibold text-violet/40">{{ $num }}</p>
                        <p class="mt-3 font-display text-lg font-semibold">{{ __('loop.'.$title) }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ __('loop.'.$body) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="loop-shell grid gap-5 py-14 sm:grid-cols-2 sm:gap-6 sm:py-20">
        <div class="rounded-[1.75rem] bg-ink p-7 text-white sm:p-9">
            <h2 class="font-display text-2xl font-semibold">{{ __('loop.for_business_title') }}</h2>
            <ul class="mt-6 space-y-4 text-sm text-white/75">
                <li class="flex gap-3"><span class="mt-0.5 text-lime">◆</span>{{ __('loop.for_business_1') }}</li>
                <li class="flex gap-3"><span class="mt-0.5 text-lime">◆</span>{{ __('loop.for_business_2') }}</li>
                <li class="flex gap-3"><span class="mt-0.5 text-lime">◆</span>{{ __('loop.for_business_3') }}</li>
            </ul>
            <a href="{{ route('business.register') }}" class="loop-btn-lime mt-8">{{ __('loop.cta_business') }}</a>
        </div>
        <div class="rounded-[1.75rem] border border-ink/10 bg-white p-7 sm:p-9">
            <h2 class="font-display text-2xl font-semibold">{{ __('loop.for_customers_title') }}</h2>
            <ul class="mt-6 space-y-4 text-sm text-ink-muted">
                <li class="flex gap-3"><span class="mt-0.5 text-violet">●</span>{{ __('loop.for_customers_1') }}</li>
                <li class="flex gap-3"><span class="mt-0.5 text-violet">●</span>{{ __('loop.for_customers_2') }}</li>
                <li class="flex gap-3"><span class="mt-0.5 text-violet">●</span>{{ __('loop.for_customers_3') }}</li>
            </ul>
            <a href="{{ route('customer.login') }}" class="loop-btn mt-8">{{ __('loop.cta_customer') }}</a>
        </div>
    </section>

    <section class="border-t border-ink/10">
        <div class="loop-shell py-14 sm:py-16">
            <h2 class="font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.countries_title') }}</h2>
            <p class="mt-3 max-w-2xl text-sm text-ink-muted sm:text-base">{{ __('loop.countries_body') }}</p>
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach (\App\Support\Countries::OPTIONS as $code => $meta)
                    <span class="rounded-full border border-ink/10 bg-white px-3.5 py-1.5 text-sm font-medium">{{ $meta['flag'] }} {{ $meta['name'] }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <x-site-footer />
</div>
</body>
</html>
