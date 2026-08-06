<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loop for business — Retain customers without cards</title>
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
    <div class="flex gap-3">
        <a href="{{ route('staff.login') }}" class="loop-btn-ghost !py-2">Staff login</a>
        <a href="{{ route('business.register') }}" class="loop-btn !py-2">Get started</a>
    </div>
</header>

<main>
    <section class="loop-shell grid items-center gap-10 py-12 lg:grid-cols-2">
        <div class="animate-fade-up">
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-mint-deep">For growing businesses</p>
            <h1 class="mt-3 font-display text-4xl font-semibold leading-tight sm:text-5xl">Keep customers coming back — no plastic cards.</h1>
            <p class="mt-5 max-w-lg text-lg text-ink-muted">Ask for a phone number. Record the sale. Loop awards points and shows rewards your front desk can apply instantly.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('business.register') }}" class="loop-btn-mint">Register your business</a>
                <a href="{{ route('staff.login') }}" class="loop-btn-ghost">I already have an account</a>
            </div>
        </div>
        <div class="relative animate-fade-up-delay">
            <div class="loop-panel overflow-hidden p-0">
                <img src="https://images.unsplash.com/photo-1556740738-b6a63e27c4df?auto=format&fit=crop&w=1400&q=80" alt="Shop counter" class="h-80 w-full object-cover">
                <div class="space-y-3 p-6">
                    <p class="font-display text-xl font-semibold">Till-first loyalty</p>
                    <p class="text-sm text-ink-muted">Walk-in or phone order — look up the customer, enter amount spent, apply a reward if they’ve earned it.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="loop-shell grid gap-8 border-t border-ink/10 py-16 md:grid-cols-3">
        <div>
            <p class="font-display text-lg font-semibold">Flexible campaigns</p>
            <p class="mt-2 text-sm text-ink-muted">Spend-to-points, birthday bonuses, welcome gifts, and 100-point discounts — start from proven templates.</p>
        </div>
        <div>
            <p class="font-display text-lg font-semibold">Front desk ready</p>
            <p class="mt-2 text-sm text-ink-muted">Owners create campaigns. Staff use phone + password on the till — every sale is audited.</p>
        </div>
        <div>
            <p class="font-display text-lg font-semibold">One phone, many shops</p>
            <p class="mt-2 text-sm text-ink-muted">Customers never get duplicated. A number already on Loop simply joins your shop wallet.</p>
        </div>
    </section>
</main>
</body>
</html>
