<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.tagline') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('discover') }}" class="text-sm font-semibold text-ink-muted">{{ __('loop.browse_campaigns') }}</a>
    </x-slot:actions>
</x-site-header>
<main class="loop-shell py-12">
    <div class="mx-auto max-w-lg text-center">
        <x-loop-logo class="mx-auto h-14 w-14" />
        <h1 class="mt-6 font-display text-4xl font-semibold">{{ __('Your loyalty lives on your phone.') }}</h1>
        <p class="mt-4 text-ink-muted">{{ __('Enter your number, set a PIN, and see points across coffee, fashion, food, and more.') }}</p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('customer.login') }}" class="loop-btn-mint">{{ __('loop.cta_customer') }}</a>
            <a href="{{ route('discover') }}" class="loop-btn-ghost">{{ __('loop.browse_campaigns') }}</a>
        </div>
    </div>
</main>
<x-site-footer />
</body>
</html>
