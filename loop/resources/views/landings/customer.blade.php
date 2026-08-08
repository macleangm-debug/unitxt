<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.customer_landing_title') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="relative min-h-screen overflow-x-hidden bg-ink">
    <x-site-header overlay>
        <x-slot:actions>
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-white/75 hover:text-white">{{ __('loop.browse_campaigns') }}</a>
            <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-white/75 hover:text-white">{{ __('loop.cta_customer') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main>
        {{-- Full-bleed hero: brand + one line + CTA only --}}
        <section class="relative min-h-[88vh] overflow-hidden">
            <div class="absolute inset-0">
                <img
                    src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=2200&q=80"
                    alt=""
                    class="h-full w-full scale-105 object-cover motion-safe:animate-[loop-slow-pan_28s_ease-in-out_infinite_alternate]"
                >
                <div class="absolute inset-0 bg-[linear-gradient(105deg,rgba(11,31,42,0.92)_0%,rgba(11,31,42,0.62)_42%,rgba(11,31,42,0.28)_100%)]"></div>
                <div class="pointer-events-none absolute -left-20 top-28 h-72 w-72 rounded-full bg-mint/20 blur-3xl motion-safe:animate-pulse"></div>
                <div class="pointer-events-none absolute bottom-0 right-0 h-80 w-80 rounded-full bg-coral/15 blur-3xl"></div>
            </div>

            <div class="loop-shell relative z-10 flex min-h-[88vh] flex-col justify-end pb-16 pt-28 sm:justify-center sm:pb-24 sm:pt-20">
                <div class="max-w-2xl">
                    <p class="font-display text-6xl font-semibold tracking-tight text-white motion-safe:animate-fade-up sm:text-7xl lg:text-8xl">Loop</p>
                    <h1 class="mt-5 max-w-xl font-display text-2xl font-semibold leading-snug text-white/95 motion-safe:animate-fade-up sm:text-3xl lg:text-[2.15rem]">
                        {{ __('loop.customer_landing_title') }}
                    </h1>
                    <p class="mt-4 max-w-md text-base leading-relaxed text-white/70 motion-safe:animate-fade-up-delay sm:text-lg">
                        {{ __('loop.customer_landing_body') }}
                    </p>
                    <div class="mt-9 flex flex-wrap gap-3 motion-safe:animate-fade-up-delay">
                        <a href="{{ route('customer.login') }}" class="loop-btn-mint">{{ __('loop.cta_customer') }}</a>
                        <a href="{{ route('discover') }}" class="rounded-xl border border-white/30 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20">
                            {{ __('loop.browse_campaigns') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-[#f7faf8] text-ink">
            <div class="loop-shell grid gap-12 py-16 sm:grid-cols-3 sm:gap-10 sm:py-20">
                @foreach ([
                    ['customer_aside_1', '01'],
                    ['customer_aside_2', '02'],
                    ['customer_aside_3', '03'],
                ] as [$key, $num])
                    <div>
                        <p class="font-display text-4xl font-semibold text-mint-deep/50">{{ $num }}</p>
                        <p class="mt-3 font-display text-xl font-semibold leading-snug">{{ __('loop.'.$key) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </main>

    <x-site-footer />
</div>
<style>
@keyframes loop-slow-pan {
    from { transform: scale(1.05) translate3d(0, 0, 0); }
    to { transform: scale(1.12) translate3d(-2%, -1%, 0); }
}
</style>
</body>
</html>
