<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $business->name }} — Loop</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<header class="loop-shell flex items-center justify-between py-6">
    <a href="{{ route('discover') }}" class="text-sm font-semibold text-ink-muted">← {{ __('loop.discover') }}</a>
    @auth
        <a href="{{ route('dashboard') }}" class="loop-btn-ghost !py-2">{{ __('loop.home') }}</a>
    @else
        <a href="{{ route('customer.login') }}" class="loop-btn-mint !py-2">{{ __('loop.cta_customer') }}</a>
    @endauth
</header>
<main class="loop-shell pb-16">
    <div class="overflow-hidden rounded-[2rem] bg-ink text-white">
        <div class="grid gap-6 p-6 sm:p-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint">{{ $business->sectorLabel() }}</p>
                <h1 class="mt-3 font-display text-4xl font-semibold sm:text-5xl">{{ $business->name }}</h1>
                <p class="mt-3 text-white/70">{{ $business->city }} · {{ $business->country }}</p>
                @if ($business->description)
                    <p class="mt-4 max-w-xl text-sm text-white/75">{{ $business->description }}</p>
                @endif
            </div>
            <div class="rounded-3xl bg-white/10 p-5 backdrop-blur">
                <p class="text-sm text-white/70">{{ __('loop.shops') }}</p>
                <ul class="mt-3 space-y-3 text-sm">
                    @foreach ($business->shops as $shop)
                        <li class="flex items-center justify-between gap-3">
                            <span>{{ $shop->name }}@if($shop->address) — {{ $shop->address }}@endif</span>
                            @if ($isCustomer && $memberships->has($shop->id))
                                <span class="shrink-0 rounded-lg bg-mint px-2 py-0.5 text-xs font-semibold text-ink">
                                    {{ number_format($memberships->get($shop->id)->points_balance) }} pts
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @unless ($isCustomer)
                    <p class="mt-3 text-xs text-white/55">{{ __('loop.sign_in_for_points') }}</p>
                @endunless
            </div>
        </div>
    </div>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.active_campaigns') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse ($campaigns as $campaign)
                <div class="loop-panel p-5">
                    <p class="font-semibold">{{ $campaign->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                </div>
            @empty
                <p class="text-ink-muted">{{ __('loop.no_live_campaigns') }}</p>
            @endforelse
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.offers_at_till') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse ($rewards as $reward)
                <div class="loop-panel p-5">
                    <p class="font-semibold">{{ $reward->name }}</p>
                    <p class="text-sm text-ink-muted">{{ $reward->points_cost }} pts · {{ $reward->label() }}</p>
                    @if ($reward->product_name)
                        <p class="mt-1 text-xs text-ink-muted">{{ $reward->product_name }}</p>
                    @endif
                </div>
            @empty
                <p class="text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
            @endforelse
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="mt-10">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.related_shops') }}</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    <a href="{{ route('discover.show', $item) }}" class="loop-panel flex items-center gap-3 p-4 transition hover:-translate-y-0.5">
                        @php $shop = $item->shops->first(); @endphp
                        @if ($shop)
                            <x-shop-logo :shop="$shop" class="h-12 w-12 rounded-2xl" />
                        @endif
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $item->name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $sectors[$item->sector] ?? '' }} · {{ $item->city }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</main>
</body>
</html>
