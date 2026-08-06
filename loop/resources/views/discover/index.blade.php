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

<main class="pb-28 pt-6">
    <div class="loop-shell">
        @if (session('status'))
            <div class="mb-4 rounded-xl border border-mint/40 bg-mint-soft px-4 py-3 text-sm">{{ session('status') }}</div>
        @endif

        <div class="flex items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.browse_campaigns') }}</h1>
                <p class="mt-2 text-sm text-ink-muted">
                    {{ $activeCity ?: __('loop.all_cities') }} · {{ $countries[$activeCountry]['name'] ?? $activeCountry }}
                    @unless ($isCustomer)
                        · {{ __('loop.sign_in_for_points') }}
                    @endunless
                </p>
            </div>
            <button type="button" @click="filtersOpen = true" class="loop-btn-ghost !px-3 !py-2 text-sm sm:hidden">{{ __('loop.filters') }}</button>
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
                    <option value="">{{ __('loop.all_cities') }}</option>
                    @foreach ($cities as $cityOption)
                        <option value="{{ $cityOption }}" @selected($activeCity === $cityOption)>{{ $cityOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.sector') }}</label>
                <select name="sector" class="loop-input" onchange="this.form.submit()">
                    <option value="">{{ __('loop.all') }}</option>
                    @foreach ($sectors as $key => $label)
                        <option value="{{ $key }}" @selected($activeSector === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @forelse ($rows as $row)
        <section class="mt-8 first:mt-6">
            <div class="loop-shell mb-3 flex items-end justify-between gap-3">
                <h2 class="font-display text-xl font-semibold sm:text-2xl">{{ $row['title'] }}</h2>
                @if ($row['key'] === 'frequent')
                    <span class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.frequent') }}</span>
                @endif
            </div>
            <div class="loop-carousel">
                @foreach ($row['businesses'] as $business)
                    @php
                        $membership = $membershipByBusinessId->get($business->id);
                        $points = $membership?->points_balance;
                    @endphp
                    <x-discover-tile
                        :business="$business"
                        :show-points="$isCustomer && $membership !== null"
                        :points="$points"
                    />
                @endforeach
            </div>
        </section>
    @empty
        <div class="loop-shell">
            <div class="loop-panel mt-10 p-8 text-center text-ink-muted">{{ __('loop.no_shops_filter') }}</div>
        </div>
    @endforelse
</main>

<div x-show="filtersOpen" x-cloak class="fixed inset-0 z-40 sm:hidden" @keydown.escape.window="filtersOpen=false">
    <div class="absolute inset-0 bg-ink/40" @click="filtersOpen=false"></div>
    <div class="absolute inset-x-0 bottom-0 rounded-t-3xl bg-white p-5 pb-8 shadow-2xl" @click.stop>
        <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-ink/15"></div>
        <h3 class="font-display text-lg font-semibold">{{ __('loop.filters') }}</h3>
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
                    <option value="">{{ __('loop.all_cities') }}</option>
                    @foreach ($cities as $cityOption)
                        <option value="{{ $cityOption }}" @selected($activeCity === $cityOption)>{{ $cityOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.sector') }}</label>
                <select name="sector" class="loop-input">
                    <option value="">{{ __('loop.all') }}</option>
                    @foreach ($sectors as $key => $label)
                        <option value="{{ $key }}" @selected($activeSector === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="loop-btn-mint w-full">{{ __('loop.apply') }}</button>
        </form>
    </div>
</div>

<x-site-footer />
</body>
</html>
