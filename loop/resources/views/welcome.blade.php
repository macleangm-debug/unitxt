<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loop — Loyalty that brings customers back</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-mint/25 blur-3xl animate-float"></div>
            <div class="absolute right-0 top-0 h-[28rem] w-[28rem] rounded-full bg-coral/15 blur-3xl"></div>
            <div class="absolute bottom-10 left-1/3 h-64 w-64 rounded-full border border-ink/5 animate-loop-spin"></div>
        </div>

        <header class="loop-shell relative z-10 flex items-center justify-between py-6">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-ink text-mint font-display text-xl font-bold">L</span>
                <span class="font-display text-3xl font-semibold tracking-tight">Loop</span>
            </div>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="loop-btn">Open dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="loop-btn-ghost">Log in</a>
                    <a href="{{ route('register') }}" class="loop-btn">Get started</a>
                @endauth
            </div>
        </header>

        <main class="relative z-10">
            <section class="loop-shell grid min-h-[78vh] items-center gap-12 py-10 lg:grid-cols-[1.05fr_0.95fr]">
                <div>
                    <p class="font-display text-6xl font-semibold tracking-tight text-ink sm:text-7xl lg:text-8xl animate-fade-up">
                        Loop
                    </p>
                    <h1 class="mt-5 max-w-xl font-display text-3xl font-semibold leading-tight text-ink sm:text-4xl animate-fade-up-delay">
                        Keep customers coming back to every shop you run.
                    </h1>
                    <p class="mt-5 max-w-lg text-lg leading-relaxed text-ink-muted animate-fade-up-delay-2">
                        A loyalty platform for small and medium businesses. Launch visit campaigns, reward points at each shop, and turn one-time buyers into regulars.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3 animate-fade-up-delay-2">
                        <a href="{{ route('register') }}" class="loop-btn-mint">Start retaining customers</a>
                        <a href="{{ route('login') }}" class="loop-btn-ghost">I already have an account</a>
                    </div>
                </div>

                <div class="relative animate-fade-up-delay">
                    <div class="absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-mint/30 via-transparent to-coral/20 blur-xl"></div>
                    <div class="relative overflow-hidden rounded-[2rem] border border-ink/10 bg-ink text-white shadow-[0_30px_80px_rgba(11,31,42,0.25)]">
                        <img
                            src="https://images.unsplash.com/photo-1556740738-b6a63e27c4df?auto=format&fit=crop&w=1400&q=80"
                            alt="Customers engaging with a local shop"
                            class="h-[28rem] w-full object-cover opacity-80"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/40 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-8">
                            <p class="font-display text-2xl font-semibold">Visit. Earn. Return.</p>
                            <p class="mt-2 max-w-sm text-sm text-white/75">
                                Customers check in with a shop code, campaigns award points instantly, and rewards close the loop.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="loop-shell grid gap-10 border-t border-ink/10 py-20 md:grid-cols-3">
                <div>
                    <p class="font-display text-xl font-semibold">Campaigns</p>
                    <p class="mt-2 text-ink-muted">Set points per visit, bonuses, daily limits, and which shops participate.</p>
                </div>
                <div>
                    <p class="font-display text-xl font-semibold">Multi-shop visits</p>
                    <p class="mt-2 text-ink-muted">Every location has a check-in code so customers earn wherever they shop.</p>
                </div>
                <div>
                    <p class="font-display text-xl font-semibold">Point wallets</p>
                    <p class="mt-2 text-ink-muted">Balances stay tied to each business, with a clear history of earns and redemptions.</p>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
