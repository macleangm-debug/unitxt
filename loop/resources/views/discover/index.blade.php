<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Discover Loop shops</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<header class="loop-shell flex items-center justify-between py-6">
    <a href="{{ route('home') }}" class="flex items-center gap-3">
        <x-loop-logo class="h-10 w-10" />
        <span class="font-display text-2xl font-semibold">Loop</span>
    </a>
    <a href="{{ route('customer.login') }}" class="loop-btn-mint !py-2">Enter phone</a>
</header>
<main class="loop-shell pb-16">
    <h1 class="font-display text-3xl font-semibold">Campaigns near you</h1>
    <p class="mt-2 text-ink-muted">Browse by sector. When you visit, the shop adds your phone — then you can track points here.</p>

    <div class="mt-6 flex flex-wrap gap-2">
        <a href="{{ route('discover') }}" class="rounded-xl px-3 py-1.5 text-sm {{ !$activeSector ? 'bg-ink text-white' : 'bg-white border border-ink/10' }}">All</a>
        @foreach ($sectors as $key => $label)
            <a href="{{ route('discover', ['sector' => $key]) }}" class="rounded-xl px-3 py-1.5 text-sm {{ $activeSector === $key ? 'bg-ink text-white' : 'bg-white border border-ink/10' }}">{{ $label }}</a>
        @endforeach
    </div>

    @forelse ($grouped as $sector => $businesses)
        <section class="mt-10">
            <h2 class="font-display text-xl font-semibold">{{ $sectors[$sector] ?? 'Other' }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($businesses as $business)
                    <a href="{{ route('discover.show', $business) }}" class="loop-panel block p-5">
                        <p class="font-display text-lg font-semibold">{{ $business->name }}</p>
                        <p class="text-sm text-ink-muted">{{ $business->city }} · {{ $business->shops_count }} shops</p>
                        @if ($business->campaigns->first())
                            <p class="mt-3 text-sm text-mint-deep">{{ $business->campaigns->first()->ruleSummary($business->currency) }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="mt-10 text-ink-muted">No businesses listed yet.</p>
    @endforelse
</main>
</body>
</html>
