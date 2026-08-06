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

<main class="pb-16">
    <section class="loop-shell">
        <div class="relative overflow-hidden rounded-[2rem] bg-ink text-white shadow-[0_30px_80px_rgba(11,31,42,0.18)]">
            <div class="pointer-events-none absolute -right-16 top-0 h-64 w-64 rounded-full bg-mint/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-10 bottom-0 h-56 w-56 rounded-full bg-coral/20 blur-3xl"></div>
            <div class="relative grid gap-8 p-6 sm:p-10 lg:grid-cols-[auto_1fr] lg:items-center">
                <div class="mx-auto lg:mx-0">
                    @if ($business->logo_path)
                        <img src="{{ asset('storage/'.$business->logo_path) }}" alt="{{ $business->name }}" class="h-36 w-36 rounded-[1.75rem] object-cover ring-4 ring-white/15 sm:h-44 sm:w-44">
                    @else
                        <div class="flex h-36 w-36 items-center justify-center rounded-[1.75rem] bg-gradient-to-br from-mint/40 to-coral/30 font-display text-5xl font-semibold text-white ring-4 ring-white/15 sm:h-44 sm:w-44">
                            {{ mb_substr($business->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint">{{ $business->sectorLabel() }}</p>
                    <h1 class="mt-3 font-display text-4xl font-semibold sm:text-5xl">{{ $business->name }}</h1>
                    <p class="mt-3 text-white/70">
                        {{ $business->city ?: ($business->shops->first()?->city) }} · {{ $business->country }}
                        @if ($business->shops->count() > 1)
                            · {{ $business->shops->count() }} {{ __('loop.branches') }}
                        @endif
                    </p>
                    @if ($business->description)
                        <p class="mt-4 max-w-xl text-sm text-white/75">{{ $business->description }}</p>
                    @endif
                    @if ($isCustomer && $totalPoints !== null && $totalPoints > 0)
                        <div class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-mint px-4 py-2 text-sm font-semibold text-ink">
                            {{ number_format($totalPoints) }} pts
                        </div>
                    @elseif (! $isCustomer)
                        <p class="mt-5 text-xs text-white/55">{{ __('loop.sign_in_for_points') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="loop-shell mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.branches') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($business->shops as $shop)
                <div class="rounded-2xl border border-ink/10 bg-white/80 p-4 backdrop-blur-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $shop->name }}</p>
                            <p class="mt-1 text-sm text-ink-muted">
                                @if ($shop->address){{ $shop->address }} · @endif{{ $shop->city }}
                            </p>
                        </div>
                        @if ($isCustomer && $memberships->has($shop->id))
                            <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-xs font-semibold">
                                {{ number_format($memberships->get($shop->id)->points_balance) }} pts
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="loop-shell mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.active_campaigns') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse ($campaigns as $campaign)
                <div class="rounded-2xl bg-gradient-to-br from-mint/20 to-white p-5 ring-1 ring-mint/20">
                    <p class="font-semibold">{{ $campaign->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                </div>
            @empty
                <p class="text-ink-muted">{{ __('loop.no_live_campaigns') }}</p>
            @endforelse
        </div>
    </section>

    <section class="loop-shell mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.offers_at_till') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse ($rewards as $reward)
                <div class="rounded-2xl bg-gradient-to-br from-coral/15 to-white p-5 ring-1 ring-coral/15">
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
        <section class="loop-shell mt-10">
            <h2 class="mb-4 font-display text-xl font-semibold">{{ __('loop.related_shops') }}</h2>
            <div class="loop-shop-grid">
                @foreach ($related as $item)
                    <x-discover-tile :business="$item" />
                @endforeach
            </div>
        </section>
    @endif
</main>
</body>
</html>
