<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('loop.browse_campaigns') }} · Loop</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans text-ink" x-data="{ filtersOpen: false }">
<x-site-header>
    <x-slot:actions>
        @auth
            <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-muted">{{ __('loop.home') }}</a>
        @else
            <a href="{{ route('customer.login') }}" class="loop-btn-mint !px-3 !py-2 text-sm">{{ __('loop.cta_customer') }}</a>
        @endauth
    </x-slot:actions>
</x-site-header>

<main class="loop-shell pb-28 pt-6">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-mint/40 bg-mint-soft px-4 py-3 text-sm">{{ session('status') }}</div>
    @endif

    <div class="flex items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-3xl font-semibold">{{ __('loop.browse_campaigns') }}</h1>
            <p class="mt-2 text-sm text-ink-muted">{{ $activeCity ?: __('All cities') }} · {{ $countries[$activeCountry]['name'] ?? $activeCountry }}</p>
        </div>
        <button type="button" @click="filtersOpen = true" class="loop-btn-ghost !px-3 !py-2 text-sm sm:hidden">{{ __('Filters') }}</button>
    </div>

    <form id="discover-filters" method="GET" action="{{ route('discover') }}" class="mt-5 hidden gap-3 sm:grid sm:grid-cols-3">
        <div>
            <label class="loop-label">{{ __('loop.country') }}</label>
            <select name="country" class="loop-input" onchange="this.form.submit()">
                @foreach ($countries as $code => $meta)
                    <option value="{{ $code }}" @selected($activeCountry === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.city') }}</label>
            <select name="city" class="loop-input" onchange="this.form.submit()">
                <option value="">{{ __('All cities') }}</option>
                @foreach ($cities as $cityOption)
                    <option value="{{ $cityOption }}" @selected($activeCity === $cityOption)>{{ $cityOption }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.sector') }}</label>
            <select name="sector" class="loop-input" onchange="this.form.submit()">
                <option value="">{{ __('All') }}</option>
                @foreach ($sectors as $key => $label)
                    <option value="{{ $key }}" @selected($activeSector === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @forelse ($groupedByCity as $cityName => $shops)
        <section class="mt-10">
            <h2 class="font-display text-lg font-semibold sm:text-xl">{{ $cityName }}</h2>
            <div class="mt-4 grid gap-3">
                @foreach ($shops as $shop)
                    <a href="{{ route('discover.show', $shop->business) }}" class="loop-panel flex items-center gap-4 p-4 transition active:scale-[0.99] hover:bg-white">
                        <x-shop-logo :shop="$shop" class="h-14 w-14 shrink-0 rounded-2xl" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-display text-base font-semibold sm:text-lg">{{ $shop->name }}</p>
                            <p class="truncate text-sm text-ink-muted">{{ $shop->business->name }} · {{ $sectors[$shop->business->sector] ?? '' }}</p>
                            @if ($shop->business->campaigns->first())
                                <p class="mt-1 truncate text-xs font-medium text-mint-deep">{{ $shop->business->campaigns->first()->ruleSummary($shop->business->currency) }}</p>
                            @endif
                        </div>
                        <span class="text-ink-muted">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="loop-panel mt-10 p-8 text-center text-ink-muted">{{ __('No shops in this filter yet.') }}</div>
    @endforelse
</main>

{{-- Mobile bottom sheet filters --}}
<div x-show="filtersOpen" x-cloak class="fixed inset-0 z-40 sm:hidden" @keydown.escape.window="filtersOpen=false">
    <div class="absolute inset-0 bg-ink/40" @click="filtersOpen=false"></div>
    <div class="absolute inset-x-0 bottom-0 rounded-t-3xl bg-white p-5 pb-8 shadow-2xl" @click.stop>
        <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-ink/15"></div>
        <h3 class="font-display text-lg font-semibold">{{ __('Filters') }}</h3>
        <form method="GET" action="{{ route('discover') }}" class="mt-4 space-y-3">
            <div>
                <label class="loop-label">{{ __('loop.country') }}</label>
                <select name="country" class="loop-input">
                    @foreach ($countries as $code => $meta)
                        <option value="{{ $code }}" @selected($activeCountry === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.city') }}</label>
                <select name="city" class="loop-input">
                    <option value="">{{ __('All cities') }}</option>
                    @foreach ($cities as $cityOption)
                        <option value="{{ $cityOption }}" @selected($activeCity === $cityOption)>{{ $cityOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.sector') }}</label>
                <select name="sector" class="loop-input">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($sectors as $key => $label)
                        <option value="{{ $key }}" @selected($activeSector === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="loop-btn-mint w-full">{{ __('Apply') }}</button>
        </form>
    </div>
</div>

<x-site-footer />
</body>
</html>
