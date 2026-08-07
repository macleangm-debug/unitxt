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
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('discover') }}" class="text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.discover') }}</a>
        @auth
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.home') }}</a>
        @else
            <a href="{{ route('customer.login') }}" class="text-sm font-semibold text-mint-deep hover:text-ink">{{ __('loop.cta_customer') }}</a>
        @endauth
    </x-slot:actions>
</x-site-header>

<main class="pb-16 pt-6">
    <section class="loop-shell">
        <div class="relative overflow-hidden rounded-[2rem] bg-ink text-white shadow-[0_30px_80px_rgba(11,31,42,0.18)]">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_85%_20%,rgba(45,212,168,0.28),transparent_40%),radial-gradient(circle_at_10%_80%,rgba(255,107,74,0.22),transparent_35%)]"></div>
            <div class="relative grid gap-5 p-5 sm:gap-6 sm:p-8 lg:grid-cols-[auto_1fr_auto] lg:items-center">
                <div class="mx-auto lg:mx-0">
                    @if ($business->logo_path)
                        <img src="{{ asset('storage/'.$business->logo_path) }}" alt="{{ $business->name }}" class="h-28 w-28 rounded-[1.5rem] object-cover ring-4 ring-white/15 sm:h-36 sm:w-36">
                    @else
                        <div class="flex h-28 w-28 items-center justify-center rounded-[1.5rem] bg-gradient-to-br from-mint/40 to-coral/30 font-display text-4xl font-semibold text-white ring-4 ring-white/15 sm:h-36 sm:w-36">
                            {{ mb_substr($business->name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mint">{{ $business->sectorLabel() }}</p>
                    <h1 class="mt-1 font-display text-3xl font-semibold leading-tight sm:text-4xl">{{ $business->name }}</h1>
                    <p class="mt-1 text-sm text-white/70">
                        {{ $business->city ?: ($business->shops->first()?->city) }} · {{ $business->country }}
                        @if ($business->shops->count() > 1)
                            · {{ $business->shops->count() }} {{ __('loop.branches') }}
                        @endif
                    </p>
                    @if ($business->description)
                        <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/75">{{ $business->description }}</p>
                    @endif
                    @if ($isCustomer && $totalPoints !== null && $totalPoints > 0)
                        <div class="mt-3 inline-flex items-center gap-2 rounded-2xl bg-mint px-3.5 py-1.5 text-sm font-semibold text-ink">
                            {{ number_format($totalPoints) }} {{ __('loop.pts') }}
                        </div>
                    @endif
                </div>
                @if ($business->hotline)
                    <div class="lg:text-right">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/50">{{ __('loop.hotline') }}</p>
                        <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mt-1.5 inline-flex items-center gap-2.5 rounded-2xl bg-white px-4 py-2.5 font-display text-base font-semibold text-ink transition hover:bg-mint">
                            <svg class="h-5 w-5 text-mint-deep" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                            <span>{{ $business->hotline }}</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="loop-shell mt-6">
        <h2 class="font-display text-lg font-semibold">{{ __('loop.branches') }}</h2>
        <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
            @foreach ($business->shops as $shop)
                <div class="rounded-2xl border border-ink/10 bg-white/80 px-4 py-3 backdrop-blur-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold leading-snug">{{ $shop->name }}</p>
                            <p class="mt-0.5 text-sm text-ink-muted">
                                @if ($shop->address){{ $shop->address }} · @endif{{ $shop->city }}
                            </p>
                            @if ($shop->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $shop->phone) }}" class="mt-1.5 inline-flex items-center gap-1.5 text-sm font-semibold text-mint-deep">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                                    {{ $shop->phone }}
                                </a>
                            @endif
                        </div>
                        @if ($isCustomer && $memberships->has($shop->id))
                            <span class="shrink-0 rounded-lg bg-mint-soft px-2.5 py-1 text-xs font-semibold">
                                {{ number_format($memberships->get($shop->id)->points_balance) }} {{ __('loop.pts') }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="loop-shell mt-6">
        <h2 class="font-display text-lg font-semibold">{{ __('loop.active_campaigns') }}</h2>
        <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
            @forelse ($campaigns as $campaign)
                <div class="rounded-2xl bg-gradient-to-br from-mint/20 to-white px-4 py-3.5 ring-1 ring-mint/20">
                    <p class="font-semibold leading-snug">{{ $campaign->displayName() }}</p>
                    <p class="mt-0.5 text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_live_campaigns') }}</p>
            @endforelse
        </div>
    </section>

    <section class="loop-shell mt-6">
        <h2 class="font-display text-lg font-semibold">{{ __('loop.offers_at_till') }}</h2>
        <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
            @forelse ($rewards as $reward)
                <div class="rounded-2xl bg-gradient-to-br from-coral/15 to-white px-4 py-3.5 ring-1 ring-coral/15">
                    <p class="font-semibold leading-snug">{{ $reward->name }}</p>
                    <p class="mt-0.5 text-sm text-ink-muted">{{ $reward->points_cost }} {{ __('loop.pts') }} · {{ $reward->label() }}</p>
                    @if ($business->hotline)
                        <a href="tel:{{ preg_replace('/\s+/', '', $business->hotline) }}" class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-mint-deep">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.58.57a1 1 0 011 1V20a1 1 0 01-1 1C10.4 21 3 13.6 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.46.57 3.58a1 1 0 01-.25 1.02l-2.2 2.19z"/></svg>
                            {{ $business->hotline }}
                        </a>
                    @endif
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_offers_yet') }}</p>
            @endforelse
        </div>
    </section>

    @if ($related->isNotEmpty())
        <section class="loop-shell mt-8">
            <h2 class="mb-3 font-display text-lg font-semibold">{{ __('loop.more_nearby') }}</h2>
            <div class="loop-shop-grid">
                @foreach ($related as $item)
                    <x-discover-tile :business="$item" />
                @endforeach
            </div>
        </section>
    @endif
</main>
<x-site-footer />
</body>
</html>
