<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.affiliates') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="min-h-screen bg-chalk">
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('affiliate.login') }}" class="loop-btn !py-2 text-sm">{{ __('loop.log_in') }}</a>
    </x-slot:actions>
</x-site-header>

<main>
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_20%,rgba(91,46,255,0.18),transparent_42%),radial-gradient(circle_at_85%_10%,rgba(200,255,61,0.16),transparent_38%)]"></div>
        <div class="loop-shell relative grid items-center gap-10 py-14 lg:grid-cols-2 lg:py-20">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-violet">{{ __('loop.affiliates') }}</p>
                <h1 class="mt-3 font-display text-5xl font-semibold tracking-tight sm:text-6xl">Loop</h1>
                <p class="mt-2 font-display text-2xl text-ink-muted sm:text-3xl">{{ __('loop.affiliate_hero_title') }}</p>
                <p class="mt-5 max-w-lg text-lg leading-relaxed text-ink-muted">{{ __('loop.affiliate_hero_body') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($enabled)
                        <a href="{{ route('affiliates.apply') }}" class="loop-btn">{{ __('loop.become_affiliate') }}</a>
                    @endif
                    <a href="{{ route('affiliates.status') }}" class="loop-btn-ghost">{{ __('loop.check_status') }}</a>
                </div>
            </div>
            <div class="loop-wallet p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.what_you_earn') }}</p>
                <p class="mt-4 font-display text-5xl font-semibold text-lime">{{ $settings['commission_percent'] }}%</p>
                <p class="mt-2 text-sm text-white/70">{{ __('loop.affiliate_commission_explain') }}</p>
                <div class="mt-8 space-y-3 border-t border-white/10 pt-5 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-white/55">{{ __('loop.example_plan') }}</span><span>TZS {{ number_format($example['plan_amount']) }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-white/55">{{ __('loop.example_discount', ['pct' => $example['discount_percent']]) }}</span><span>- TZS {{ number_format($example['discount_amount']) }}</span></div>
                    <div class="flex justify-between gap-3 border-t border-white/10 pt-3"><span class="text-white/55">{{ __('loop.example_net') }}</span><span>TZS {{ number_format($example['net_amount']) }}</span></div>
                    <div class="flex justify-between gap-3 text-lime"><span>{{ __('loop.example_commission', ['pct' => $example['commission_percent']]) }}</span><span class="font-display text-xl font-semibold">TZS {{ number_format($example['commission_amount']) }}</span></div>
                </div>
                @if ($settings['attribution_enabled'])
                    <p class="mt-5 text-xs text-white/55">{{ __('loop.affiliate_attribution_note', ['months' => $settings['attribution_months']]) }}</p>
                @endif
            </div>
        </div>
    </section>

    <section class="loop-shell grid gap-8 border-t border-ink/10 py-16 md:grid-cols-3">
        @foreach ([
            ['01', 'affiliate_step_1_title', 'affiliate_step_1_body'],
            ['02', 'affiliate_step_2_title', 'affiliate_step_2_body'],
            ['03', 'affiliate_step_3_title', 'affiliate_step_3_body'],
        ] as [$num, $title, $body])
            <div>
                <p class="font-display text-4xl font-semibold text-violet/40">{{ $num }}</p>
                <h3 class="mt-3 font-display text-xl font-semibold">{{ __("loop.$title") }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ __("loop.$body") }}</p>
            </div>
        @endforeach
    </section>
</main>
<x-site-footer />
@include('partials.confirm-modal')
</div>
</body>
</html>
