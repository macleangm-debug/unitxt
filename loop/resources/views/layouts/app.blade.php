<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="view-transition" content="same-origin">
    <meta http-equiv="Permissions-Policy" content="notifications=(), push=(), interest-cohort=()">
    <title>{{ $title ?? config('app.name', 'Loop') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans" x-data="loopPageMotion()" autocomplete="off">
@php $isCustomer = auth()->user()?->isCustomer(); @endphp
<div @class(['min-h-screen', 'pb-nav md:pb-0' => $isCustomer])>
    @include('layouts.navigation')
    @isset($header)
        <header class="loop-shell pt-5 pb-1 sm:pt-8 sm:pb-2">{{ $header }}</header>
    @endisset
    <main class="loop-shell py-5 sm:py-6 {{ $isCustomer ? 'pb-8' : 'pb-16' }}">
        @if (session('status') && ! session('all_set') && ! session('confirm'))
            <div class="mb-6 rounded-2xl border border-lime/50 bg-lime-soft px-4 py-3 text-sm text-ink">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </main>
</div>
<div
    class="loop-page-veil"
    :class="{ 'is-on': transitioning, 'is-morph': morphing }"
    aria-hidden="true"
></div>

@php
    $confirm = session('confirm');
    if (session('all_set')) {
        $confirm = [
            'title' => __('loop.all_set_title'),
            'body' => __('loop.all_set_body'),
            'cta' => __('loop.start_selling'),
            'url' => route('till.index'),
            'celebrate' => (bool) \App\Support\GrowthSettings::settings()['onboarding_celebrate'],
        ];
    }
@endphp
@include('partials.confirm-modal', ['confirm' => $confirm])
</body>
</html>
