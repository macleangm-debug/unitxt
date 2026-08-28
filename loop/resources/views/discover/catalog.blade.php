@if ($isCustomer)
    <x-app-layout>
        @include('discover.partials.catalog')
    </x-app-layout>
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }} — {{ $business->name }}</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="min-h-screen bg-chalk">
    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('discover.show', $business) }}" class="whitespace-nowrap text-sm font-semibold text-ink-muted hover:text-ink">{{ $business->name }}</a>
            <a href="{{ route('customer.login') }}" class="whitespace-nowrap text-sm font-semibold text-violet hover:text-ink">{{ __('loop.cta_customer') }}</a>
        </x-slot:actions>
    </x-site-header>

    <main class="loop-shell pb-16 pt-6">
        @include('discover.partials.catalog')
    </main>
    <x-site-footer />
</div>
</body>
</html>
@endif
