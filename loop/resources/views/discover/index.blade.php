<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('loop.browse_campaigns') }} · Loop</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<header class="sticky top-0 z-20 border-b border-ink/5 bg-white/80 backdrop-blur-md">
    <div class="loop-shell flex items-center justify-between gap-3 py-3">
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <x-loop-logo class="h-9 w-9" />
            <span class="font-display text-xl font-semibold">Loop</span>
        </a>
        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-ink-muted">Home</a>
            @else
                <a href="{{ route('customer.login') }}" class="loop-btn-mint !px-3 !py-2 text-sm">{{ __('loop.cta_customer') }}</a>
            @endauth
        </div>
    </div>
</header>

<main class="loop-shell pb-20 pt-6">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-mint/40 bg-mint-soft px-4 py-3 text-sm">{{ session('status') }}</div>
    @endif

    <h1 class="font-display text-3xl font-semibold">{{ __('loop.browse_campaigns') }}</h1>
    <p class="mt-2 text-sm text-ink-muted">Find places near you. Filter by country, city, and what you love.</p>

    <form method="GET" action="{{ route('discover') }}" class="mt-5 space-y-3">
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="loop-label">{{ __('loop.country') }}</label>
                <select name="country" class="loop-input" onchange="this.form.submit()">
                    @foreach ($countries as $code => $meta)
                        <option value="{{ $code }}" @selected($activeCountry === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">City</label>
                <select name="city" class="loop-input" onchange="this.form.submit()">
                    <option value="">All cities</option>
                    @foreach ($cities as $cityOption)
                        <option value="{{ $cityOption }}" @selected($activeCity === $cityOption)>{{ $cityOption }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">Sector</label>
                <select name="sector" class="loop-input" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach ($sectors as $key => $label)
                        <option value="{{ $key }}" @selected($activeSector === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if (!empty($interests) && !$activeSector)
            <p class="text-xs text-mint-deep">Showing your interests first: {{ collect($interests)->map(fn($i) => $sectors[$i] ?? $i)->join(', ') }}</p>
        @endif
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
                            <p class="truncate text-sm text-ink-muted">
                                {{ $shop->business->name }} · {{ $sectors[$shop->business->sector] ?? '' }}
                            </p>
                            @if ($shop->business->campaigns->first())
                                <p class="mt-1 truncate text-xs font-medium text-mint-deep">
                                    {{ $shop->business->campaigns->first()->ruleSummary($shop->business->currency) }}
                                </p>
                            @endif
                        </div>
                        <span class="text-ink-muted">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="loop-panel mt-10 p-8 text-center text-ink-muted">
            No shops in this filter yet. Try another city — or ask your favourite place to join Loop.
        </div>
    @endforelse
</main>
</body>
</html>
