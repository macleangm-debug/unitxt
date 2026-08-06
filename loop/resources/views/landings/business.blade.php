<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.with_your_phone') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('staff.login') }}" class="loop-btn-ghost !py-2 text-sm">{{ __('loop.staff_login') }}</a>
        <a href="{{ route('business.register') }}" class="loop-btn !py-2 text-sm">{{ __('loop.get_started') }}</a>
    </x-slot:actions>
</x-site-header>

<main>
    <section class="loop-shell grid items-center gap-10 py-12 lg:grid-cols-2">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.business') }}</p>
            <h1 class="mt-3 font-display text-4xl font-semibold leading-tight sm:text-5xl">{{ __('loop.with_your_phone') }}</h1>
            <p class="mt-5 max-w-lg text-lg text-ink-muted">{{ __('Ask for a phone number. Record the sale. Loop awards points and shows offers your team can apply instantly.') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('business.register') }}" class="loop-btn-mint">{{ __('loop.cta_business') }}</a>
                <a href="{{ route('staff.login') }}" class="loop-btn-ghost">{{ __('loop.staff_login') }}</a>
            </div>
        </div>
        <div class="loop-panel overflow-hidden p-0">
            <img src="https://images.unsplash.com/photo-1556740738-b6a63e27c4df?auto=format&fit=crop&w=1400&q=80" alt="" class="h-72 w-full object-cover sm:h-80">
            <div class="space-y-2 p-6">
                <p class="font-display text-xl font-semibold">{{ __('Sale-first loyalty') }}</p>
                <p class="text-sm text-ink-muted">{{ __('Walk-in or phone order — look up the customer, enter amount spent, apply an offer if they’ve earned it.') }}</p>
            </div>
        </div>
    </section>

    <section class="loop-shell grid gap-6 border-t border-ink/10 py-16 md:grid-cols-3">
        <div class="rounded-3xl border border-ink/10 bg-white p-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-mint/30 to-coral/20 font-display text-lg">◎</div>
            <p class="mt-4 font-display text-lg font-semibold">{{ __('loop.flex_campaigns') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('Spend-to-points, birthday bonuses, welcome gifts, product pushes, and visit streaks.') }}</p>
        </div>
        <div class="rounded-3xl border border-ink/10 bg-white p-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-mint/30 to-coral/20 font-display text-lg">▣</div>
            <p class="mt-4 font-display text-lg font-semibold">{{ __('loop.front_desk_ready') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('Owners create campaigns. Staff use phone + password on Sale — every sale is audited.') }}</p>
        </div>
        <div class="rounded-3xl border border-ink/10 bg-white p-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-mint/30 to-coral/20 font-display text-lg">◉</div>
            <p class="mt-4 font-display text-lg font-semibold">{{ __('loop.one_phone') }}</p>
            <p class="mt-2 text-sm text-ink-muted">{{ __('Customers never get duplicated. A number already on Loop simply joins your shop wallet.') }}</p>
        </div>
    </section>
</main>
<x-site-footer />
</body>
</html>
