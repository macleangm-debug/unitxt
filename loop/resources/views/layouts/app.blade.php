<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Permissions-Policy" content="notifications=(), push=()">
    <title>{{ $title ?? config('app.name', 'Loop') }}</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans" x-data="loopPageMotion()">
<x-page-skeleton variant="app" />
@php $user = auth()->user(); $hasMobileNav = \App\Support\MobileNav::enabled($user); @endphp
<div @class(['min-h-screen loop-has-bottom-nav' => $hasMobileNav, 'min-h-screen' => ! $hasMobileNav, 'pb-nav md:pb-0' => $hasMobileNav])>
    @include('layouts.navigation')
    @isset($header)
        <header class="loop-shell pt-5 pb-1 sm:pt-8 sm:pb-2">{{ $header }}</header>
    @endisset
    <main class="loop-shell py-5 sm:py-6 {{ $hasMobileNav ? 'pb-8' : 'pb-16' }}">
        {{ $slot }}
    </main>
</div>
@if ($hasMobileNav)
    <x-loop-bottom-nav />
@endif
<div
    class="loop-page-veil"
    :class="{ 'is-on': transitioning, 'is-morph': morphing }"
    aria-hidden="true"
></div>

@php
    $confirm = \App\Support\Confirm::resolve(session('confirm'), session('status'), $errors ?? null);
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
