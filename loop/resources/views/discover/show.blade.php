<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $business->name }} on Loop</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<header class="loop-shell flex items-center justify-between py-6">
    <a href="{{ route('discover') }}" class="text-sm font-semibold text-ink-muted">← Discover</a>
    <a href="{{ route('customer.login') }}" class="loop-btn-mint !py-2">Enter phone</a>
</header>
<main class="loop-shell pb-16">
    <h1 class="font-display text-4xl font-semibold">{{ $business->name }}</h1>
    <p class="mt-2 text-ink-muted">{{ $business->sectorLabel() }} · {{ $business->city }} · {{ $business->country }}</p>
    <p class="mt-4 max-w-2xl text-ink-muted">{{ $business->description }}</p>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">Live campaigns</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @forelse ($campaigns as $campaign)
                <div class="loop-panel p-5">
                    <p class="font-semibold">{{ $campaign->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
                </div>
            @empty
                <p class="text-ink-muted">No active campaigns.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">Rewards at the till</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ($rewards as $reward)
                <div class="loop-panel p-5">
                    <p class="font-semibold">{{ $reward->name }}</p>
                    <p class="text-sm text-ink-muted">{{ $reward->points_cost }} points · {{ $reward->label() }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">Shops</h2>
        <ul class="mt-3 space-y-2 text-sm text-ink-muted">
            @foreach ($business->shops as $shop)
                <li>{{ $shop->name }}@if($shop->address) — {{ $shop->address }}@endif</li>
            @endforeach
        </ul>
    </section>
</main>
</body>
</html>
