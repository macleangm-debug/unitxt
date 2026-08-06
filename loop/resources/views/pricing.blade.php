<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('loop.pricing') }} · Loop</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">
    <header class="border-b border-ink/5 bg-white/80 backdrop-blur">
        <div class="loop-shell flex h-16 items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <x-loop-logo class="h-9 w-9" />
                <span class="font-display text-xl font-semibold">Loop</span>
            </a>
            <a href="{{ route('business.register') }}" class="loop-btn-mint !py-2">{{ __('loop.cta_business') }}</a>
        </div>
    </header>

    <main class="loop-shell py-12">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">Loop</p>
            <h1 class="mt-3 font-display text-4xl font-semibold">{{ __('loop.pricing_title') }}</h1>
            <p class="mt-3 text-ink-muted">{{ __('loop.pricing_blurb') }}</p>
        </div>

        <div class="mt-10 grid gap-4 lg:grid-cols-4">
            @foreach ($plans as $plan)
                <div class="rounded-[1.75rem] border border-ink/10 bg-white/90 p-6 shadow-[0_20px_60px_rgba(11,31,42,0.06)] {{ $plan->key === 'growth' ? 'ring-2 ring-mint' : '' }}">
                    @if ($plan->key === 'growth')
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.most_popular') }}</p>
                    @endif
                    <p class="font-display text-xl font-semibold">{{ $plan->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $plan->tagline }}</p>
                    <p class="mt-4 font-display text-2xl font-semibold">{{ $plan->priceLabel() }}</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                        @foreach ($plan->features ?? [] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('business.register') }}" class="loop-btn-mint mt-6 inline-flex w-full justify-center !py-2.5">{{ __('loop.get_started') }}</a>
                </div>
            @endforeach
        </div>

        <p class="mx-auto mt-10 max-w-xl text-center text-sm text-ink-muted">{{ __('loop.pricing_referral_note') }}</p>
    </main>

    <x-site-footer />
</body>
</html>
