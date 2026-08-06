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
        <div class="absolute -left-20 top-16 h-72 w-72 rounded-full bg-mint/25 blur-3xl animate-float"></div>
        <div class="absolute right-0 top-0 h-[26rem] w-[26rem] rounded-full bg-coral/15 blur-3xl"></div>
    </div>

    <header class="loop-shell relative z-10 flex items-center justify-between gap-3 py-5">
        <div class="flex items-center gap-2.5">
            <x-loop-logo class="h-10 w-10" />
            <span class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">Loop</span>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            <div class="flex rounded-xl border border-ink/10 bg-white/70 p-0.5 text-xs font-semibold">
                <a href="{{ route('locale', 'en') }}" class="rounded-lg px-2.5 py-1.5 {{ app()->getLocale() === 'en' ? 'bg-ink text-white' : 'text-ink-muted' }}">EN</a>
                <a href="{{ route('locale', 'sw') }}" class="rounded-lg px-2.5 py-1.5 {{ app()->getLocale() === 'sw' ? 'bg-ink text-white' : 'text-ink-muted' }}">SW</a>
            </div>
            <a href="{{ route('discover') }}" class="hidden text-sm font-semibold text-ink-muted hover:text-ink sm:inline">{{ __('loop.browse_campaigns') }}</a>
        </div>
    </header>

    {{-- Hero / chooser --}}
    <main class="loop-shell relative z-10 grid items-center gap-8 py-8 lg:min-h-[72vh] lg:grid-cols-2 lg:gap-12 lg:py-12">
        <div class="animate-fade-up">
            <p class="font-display text-5xl font-semibold tracking-tight sm:text-6xl lg:text-7xl">Loop</p>
            <p class="mt-3 font-display text-xl text-ink-soft sm:text-2xl lg:text-3xl">{{ __('loop.tagline') }}</p>
            <p class="mt-4 max-w-md text-base leading-relaxed text-ink-muted sm:text-lg">
                {{ __('loop.hero_body') }}
            </p>
        </div>

        <div class="grid gap-3 animate-fade-up-delay sm:gap-4">
            <a href="{{ route('landing.business') }}" class="loop-panel group block p-5 transition active:scale-[0.99] sm:p-7 hover:-translate-y-0.5 hover:bg-white">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep sm:text-sm">{{ __('loop.business') }}</p>
                <h2 class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ __('loop.im_business') }}</h2>
                <p class="mt-2 text-sm text-ink-muted sm:text-base">{{ __('loop.business_blurb') }}</p>
                <span class="mt-4 inline-flex text-sm font-semibold text-ink group-hover:text-mint-deep">{{ __('loop.continue') }} →</span>
            </a>

            <a href="{{ route('landing.customer') }}" class="loop-panel group block p-5 transition active:scale-[0.99] sm:p-7 hover:-translate-y-0.5 hover:bg-white">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-coral sm:text-sm">{{ __('loop.customer') }}</p>
                <h2 class="mt-2 font-display text-xl font-semibold sm:text-2xl">{{ __('loop.im_customer') }}</h2>
                <p class="mt-2 text-sm text-ink-muted sm:text-base">{{ __('loop.customer_blurb') }}</p>
                <span class="mt-4 inline-flex text-sm font-semibold text-ink group-hover:text-coral">{{ __('loop.continue') }} →</span>
            </a>
        </div>
    </main>

    {{-- How it works --}}
    <section class="relative z-10 border-t border-ink/10 bg-white/40">
        <div class="loop-shell py-14 sm:py-20">
            <h2 class="font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.how_title') }}</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-3">
                <div>
                    <p class="font-display text-4xl font-semibold text-mint-deep/40">01</p>
                    <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.how_1_title') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ __('loop.how_1_body') }}</p>
                </div>
                <div>
                    <p class="font-display text-4xl font-semibold text-mint-deep/40">02</p>
                    <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.how_2_title') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ __('loop.how_2_body') }}</p>
                </div>
                <div>
                    <p class="font-display text-4xl font-semibold text-mint-deep/40">03</p>
                    <p class="mt-2 font-display text-lg font-semibold">{{ __('loop.how_3_title') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ __('loop.how_3_body') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Audiences --}}
    <section class="loop-shell grid gap-6 py-14 sm:grid-cols-2 sm:py-20">
        <div class="loop-panel p-6 sm:p-8">
            <h2 class="font-display text-xl font-semibold sm:text-2xl">{{ __('loop.for_business_title') }}</h2>
            <ul class="mt-5 space-y-3 text-sm text-ink-muted">
                <li class="flex gap-2"><span class="text-mint-deep">▸</span>{{ __('loop.for_business_1') }}</li>
                <li class="flex gap-2"><span class="text-mint-deep">▸</span>{{ __('loop.for_business_2') }}</li>
                <li class="flex gap-2"><span class="text-mint-deep">▸</span>{{ __('loop.for_business_3') }}</li>
            </ul>
            <a href="{{ route('business.register') }}" class="loop-btn mt-8 w-full sm:w-auto">{{ __('loop.cta_business') }}</a>
        </div>
        <div class="loop-panel p-6 sm:p-8">
            <h2 class="font-display text-xl font-semibold sm:text-2xl">{{ __('loop.for_customers_title') }}</h2>
            <ul class="mt-5 space-y-3 text-sm text-ink-muted">
                <li class="flex gap-2"><span class="text-coral">▸</span>{{ __('loop.for_customers_1') }}</li>
                <li class="flex gap-2"><span class="text-coral">▸</span>{{ __('loop.for_customers_2') }}</li>
                <li class="flex gap-2"><span class="text-coral">▸</span>{{ __('loop.for_customers_3') }}</li>
            </ul>
            <a href="{{ route('customer.login') }}" class="loop-btn-mint mt-8 w-full sm:w-auto">{{ __('loop.cta_customer') }}</a>
        </div>
    </section>

    {{-- Countries --}}
    <section class="border-t border-ink/10 bg-ink text-white">
        <div class="loop-shell py-14 sm:py-16">
            <h2 class="font-display text-2xl font-semibold sm:text-3xl">{{ __('loop.countries_title') }}</h2>
            <p class="mt-3 max-w-2xl text-sm text-white/70 sm:text-base">{{ __('loop.countries_body') }}</p>
            <div class="mt-8 flex flex-wrap gap-2">
                @foreach (\App\Support\Countries::OPTIONS as $code => $meta)
                    <span class="rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-sm">{{ $meta['flag'] }} {{ $meta['name'] }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <footer class="loop-shell flex flex-wrap items-center justify-between gap-3 py-8 text-sm text-ink-muted">
        <div class="flex items-center gap-2">
            <x-loop-logo class="h-7 w-7" />
            <span class="font-display font-semibold text-ink">Loop</span>
        </div>
        <a href="{{ route('discover') }}" class="font-semibold hover:text-ink">{{ __('loop.browse_campaigns') }}</a>
    </footer>
</div>
</body>
</html>
