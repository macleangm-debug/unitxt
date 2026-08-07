<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Loop') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans">
<div class="min-h-screen">
    @include('layouts.navigation')
    @isset($header)
        <header class="loop-shell pt-8 pb-2"><div class="animate-fade-up">{{ $header }}</div></header>
    @endisset
    <main class="loop-shell py-6 pb-16">
        @if (session('status') && ! session('all_set') && ! session('confirm'))
            <div class="mb-6 rounded-xl border border-mint/40 bg-mint-soft px-4 py-3 text-sm text-ink">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </main>
</div>

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
