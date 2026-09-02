@if ($inApp ?? false)
    <x-app-layout>
        <x-slot name="header">
            <h1 class="loop-page-title font-display text-3xl font-semibold">{{ __('loop.help_faqs') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.help_faqs_blurb') }}</p>
        </x-slot>
        @include('help.partials.body')
    </x-app-layout>
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('loop.help_faqs') }} · Loop</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="min-h-screen bg-chalk">
    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('customer.login') }}" class="loop-btn !px-3 !py-2 text-sm">{{ __('loop.cta_customer') }}</a>
        </x-slot:actions>
    </x-site-header>
    <div class="loop-shell py-10">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.help_faqs') }}</h1>
        <p class="mt-2 text-ink-muted">{{ __('loop.help_faqs_blurb') }}</p>
        @include('help.partials.body')
    </div>
    <x-site-footer />
</div>
</body>
</html>
@endif
