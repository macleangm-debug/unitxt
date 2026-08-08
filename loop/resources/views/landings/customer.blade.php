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
            <a href="{{ route('discover') }}" class="text-sm font-semibold text-white/80 hover:text-white">{{ __('loop.browse_campaigns') }}</a>
            <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-white/80 hover:text-white">{{ __('loop.cta_customer') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main>
        {{-- One composition: brand + headline + line + CTA on a full-bleed shop photo --}}
        <section class="relative min-h-[100svh] overflow-hidden">
            <div class="absolute inset-0">
                <img
                    src="https://images.unsplash.com/photo-1556745753-b2904692b3cd?auto=format&fit=crop&w=2200&q=80"
                    alt=""
                    class="h-full w-full scale-105 object-cover object-[center_30%] motion-safe:animate-[loop-slow-pan_32s_ease-in-out_infinite_alternate]"
                >
                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(11,31,42,0.55)_0%,rgba(11,31,42,0.35)_38%,rgba(11,31,42,0.82)_100%)]"></div>
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(11,31,42,0.72)_0%,rgba(11,31,42,0.35)_55%,rgba(11,31,42,0.15)_100%)]"></div>
                <div class="pointer-events-none absolute -left-16 bottom-24 h-64 w-64 rounded-full bg-mint/25 blur-3xl motion-safe:animate-pulse"></div>
            </div>

            <div class="loop-shell relative z-10 flex min-h-[100svh] flex-col justify-end pb-14 pt-[5.5rem] sm:justify-end sm:pb-20 sm:pt-28 lg:pb-24">
                <div class="max-w-xl motion-safe:animate-fade-up">
                    <p class="font-display text-[clamp(3.25rem,12vw,6.5rem)] font-semibold leading-[0.92] tracking-tight text-white">
                        Loop
                    </p>
                    <h1 class="mt-5 max-w-lg font-display text-[clamp(1.35rem,4.2vw,2rem)] font-semibold leading-snug text-white/95">
                        {{ __('loop.customer_landing_title') }}
                    </h1>
                    <p class="mt-4 max-w-md text-base leading-relaxed text-white/70 sm:text-lg">
                        {{ __('loop.customer_landing_body') }}
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:mt-10 sm:flex-row sm:items-center">
                        <a href="{{ route('customer.login') }}" class="loop-btn-mint w-full justify-center sm:w-auto">
                            {{ __('loop.cta_customer') }}
                        </a>
                        <a href="{{ route('discover') }}" class="inline-flex items-center justify-center px-1 py-2 text-sm font-semibold text-white/80 underline-offset-4 transition hover:text-white hover:underline sm:px-3">
                            {{ __('loop.browse_campaigns') }} →
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-ink/5 bg-[#f4f8f6] text-ink">
            <div class="loop-shell grid gap-10 py-14 sm:grid-cols-3 sm:gap-8 sm:py-16">
                @foreach ([
                    ['customer_aside_1', '01'],
                    ['customer_aside_2', '02'],
                    ['customer_aside_3', '03'],
                ] as [$key, $num])
                    <div class="motion-safe:animate-fade-up">
                        <p class="font-display text-3xl font-semibold tracking-tight text-mint-deep/45 sm:text-4xl">{{ $num }}</p>
                        <p class="mt-3 max-w-[16rem] font-display text-lg font-semibold leading-snug sm:text-xl">{{ __('loop.'.$key) }}</p>
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
    to { transform: scale(1.1) translate3d(-1.5%, -1%, 0); }
}
</style>
</body>
</html>
