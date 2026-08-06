<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Loop') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans text-ink antialiased">
<div class="min-h-screen flex flex-col">
    <div class="loop-shell flex items-center justify-between py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <x-loop-logo class="h-9 w-9" />
            <span class="font-display text-xl font-semibold">Loop</span>
        </a>
        <div class="flex rounded-xl border border-ink/10 bg-white p-0.5 text-xs font-semibold">
            <a href="{{ route('locale', 'en') }}" class="rounded-lg px-2.5 py-1.5 {{ app()->getLocale() === 'en' ? 'bg-ink text-white' : 'text-ink-muted' }}">EN</a>
            <a href="{{ route('locale', 'sw') }}" class="rounded-lg px-2.5 py-1.5 {{ app()->getLocale() === 'sw' ? 'bg-ink text-white' : 'text-ink-muted' }}">SW</a>
        </div>
    </div>
    <div class="flex flex-1 items-start justify-center px-4 pb-10">
        <div class="w-full max-w-md loop-panel px-6 py-7">{{ $slot }}</div>
    </div>
</div>
</body>
</html>
