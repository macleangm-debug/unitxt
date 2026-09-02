@props([
    'title' => null,
])

@auth
    <x-app-layout>
        @isset($header)
            <x-slot name="header">{{ $header }}</x-slot>
        @endisset
        {{ $slot }}
    </x-app-layout>
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? __('loop.stories') }} · Loop</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="min-h-screen bg-chalk" x-data="loopPageMotion()">
    <div class="pointer-events-none fixed inset-0 -z-10">
        <div class="absolute -left-20 top-24 h-72 w-72 rounded-full bg-violet/10 blur-3xl"></div>
        <div class="absolute right-0 top-0 h-80 w-80 rounded-full bg-lime/15 blur-3xl"></div>
    </div>
    <x-site-header>
        <x-slot:actions>
            <a href="{{ route('stories.index') }}" class="whitespace-nowrap text-sm font-semibold {{ request()->routeIs('stories.*') ? 'text-violet' : 'text-ink-muted hover:text-ink' }}">{{ __('loop.stories') }}</a>
            <a href="{{ route('discover') }}" class="whitespace-nowrap text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.browse_campaigns') }}</a>
            <a href="{{ route('customer.login') }}" class="loop-btn !px-3 !py-2 text-sm">{{ __('loop.cta_customer') }}</a>
        </x-slot:actions>
    </x-site-header>
    @isset($header)
        <header class="loop-shell pt-5 pb-1 sm:pt-8 sm:pb-2">{{ $header }}</header>
    @endisset
    <main class="loop-shell py-5 sm:py-6 pb-16">
        {{ $slot }}
    </main>
    <x-site-footer />
    <div class="loop-page-veil" :class="{ 'is-on': transitioning }" aria-hidden="true"></div>
</div>
<x-page-skeleton variant="public" />
</body>
</html>
@endauth
