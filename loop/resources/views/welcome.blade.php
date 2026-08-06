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
<div class="relative min-h-screen overflow-x-hidden">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 top-10 h-80 w-80 rounded-full bg-mint/20 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-[28rem] w-[28rem] rounded-full bg-coral/10 blur-3xl"></div>
    </div>

    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('discover') }}" class="hidden text-sm font-semibold text-ink-muted hover:text-ink md:inline">{{ __('loop.browse_campaigns') }}</a>
            <a href="{{ route('home') }}#pricing" class="hidden text-sm font-semibold text-ink-muted hover:text-ink md:inline">{{ __('loop.pricing') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main class="loop-shell relative z-10 grid items-center gap-8 py-10 lg:min-h-[70vh] lg:grid-cols-2 lg:gap-14 lg:py-16">
        <div class="animate-fade-up">
            <p class="font-display text-5xl font-semibold tracking-tight sm:text-6xl lg:text-7xl">Loop</p>
            <p class="mt-3 font-display text-2xl text-ink-soft sm:text-3xl">{{ __('loop.tagline') }}</p>
            <p class="mt-4 max-w-md text-base leading-relaxed text-ink-muted sm:text-lg">{{ __('loop.hero_body') }}</p>
        </div>

        <div class="grid gap-4 animate-fade-up-delay">
            <a href="{{ route('landing.business') }}" class="group relative overflow-hidden rounded-3xl border border-ink/10 bg-ink p-6 text-white shadow-[0_24px_60px_rgba(11,31,42,0.18)] transition hover:-translate-y-0.5 sm:p-8">
                <div class="pointer-events-none absolute -right-8 -top-8 h-36 w-36 rounded-full bg-gradient-to-br from-mint/40 to-coral/30 blur-2xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-mint">{{ __('loop.business') }}</p>
                <h2 class="mt-3 font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.im_business') }}</h2>
                <p class="mt-3 max-w-sm text-sm text-white/70 sm:text-base">{{ __('loop.business_blurb') }}</p>
                <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-mint">{{ __('loop.continue') }} <span aria-hidden="true">→</span></span>
            </a>

            <a href="{{ route('landing.customer') }}" class="group relative overflow-hidden rounded-3xl border border-ink/10 bg-white/90 p-6 shadow-[0_18px_50px_rgba(11,31,42,0.08)] transition hover:-translate-y-0.5 sm:p-8">
                <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-gradient-to-br from-coral/25 to-mint/20 blur-xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-coral">{{ __('loop.customer') }}</p>
                <h2 class="mt-3 font-display text-2xl font-semibold text-ink sm:text-3xl">{{ __('loop.im_customer') }}</h2>
                <p class="mt-3 max-w-sm text-sm text-ink-muted sm:text-base">{{ __('loop.customer_blurb') }}</p>
                <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-ink">{{ __('loop.continue') }} <span aria-hidden="true">→</span></span>
            </a>
        </div>
    </main>

    <section id="how" class="relative z-10 border-t border-ink/10 bg-white/50">
        <div class="loop-shell py-14 sm:py-20">
            <h2 class="font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.how_title') }}</h2>
            <div class="mt-10 grid gap-8 sm:grid-cols-3">
                @foreach ([
                    ['01', 'how_1_title', 'how_1_body'],
                    ['02', 'how_2_title', 'how_2_body'],
                    ['03', 'how_3_title', 'how_3_body'],
                ] as [$num, $title, $body])
                    <div>
                        <p class="font-display text-4xl font-semibold bg-gradient-to-r from-mint-deep to-coral bg-clip-text text-transparent">{{ $num }}</p>
                        <p class="mt-3 font-display text-lg font-semibold">{{ __('loop.'.$title) }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ __('loop.'.$body) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="loop-shell grid gap-5 py-14 sm:grid-cols-2 sm:gap-6 sm:py-20">
        <div class="rounded-3xl border border-ink/10 bg-ink p-7 text-white sm:p-9">
            <h2 class="font-display text-2xl font-semibold">{{ __('loop.for_business_title') }}</h2>
            <ul class="mt-6 space-y-4 text-sm text-white/75">
                <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-mint/20 text-mint">◆</span>{{ __('loop.for_business_1') }}</li>
                <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-mint/20 text-mint">◆</span>{{ __('loop.for_business_2') }}</li>
                <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-mint/20 text-mint">◆</span>{{ __('loop.for_business_3') }}</li>
            </ul>
            <a href="{{ route('business.register') }}" class="mt-8 inline-flex rounded-xl bg-mint px-5 py-3 text-sm font-semibold text-ink">{{ __('loop.cta_business') }}</a>
        </div>
        <div class="rounded-3xl border border-ink/10 bg-white p-7 sm:p-9">
            <h2 class="font-display text-2xl font-semibold">{{ __('loop.for_customers_title') }}</h2>
            <ul class="mt-6 space-y-4 text-sm text-ink-muted">
                <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-coral/15 text-coral">●</span>{{ __('loop.for_customers_1') }}</li>
                <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-coral/15 text-coral">●</span>{{ __('loop.for_customers_2') }}</li>
                <li class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-coral/15 text-coral">●</span>{{ __('loop.for_customers_3') }}</li>
            </ul>
            <a href="{{ route('customer.login') }}" class="mt-8 inline-flex rounded-xl bg-ink px-5 py-3 text-sm font-semibold text-white">{{ __('loop.cta_customer') }}</a>
        </div>
    </section>

    <section id="pricing" class="relative z-10 border-t border-ink/10 bg-white/60">
        <div class="loop-shell py-14 sm:py-20">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-mint-deep">{{ __('loop.pricing') }}</p>
                <h2 class="mt-3 font-display text-3xl font-semibold sm:text-4xl">{{ __('loop.pricing_title') }}</h2>
                <p class="mt-3 text-ink-muted">{{ __('loop.pricing_blurb') }}</p>
            </div>
            <x-pricing-grid :plans="$plans" :cta-route="route('business.register')" />
            <div class="mt-8 text-center">
                <a href="{{ route('pricing') }}" class="text-sm font-semibold text-mint-deep underline underline-offset-4">{{ __('loop.see_full_pricing') }}</a>
            </div>
        </div>
    </section>

    <section class="border-t border-ink/10 bg-chalk-warm/40">
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
