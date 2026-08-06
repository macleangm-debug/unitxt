@props(['aside'])
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
<div class="min-h-screen lg:grid lg:grid-cols-2">
    <aside class="relative hidden overflow-hidden bg-ink lg:block">
        <div class="pointer-events-none absolute -left-10 top-20 h-64 w-64 rounded-full bg-mint/25 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-10 right-0 h-72 w-72 rounded-full bg-coral/20 blur-3xl"></div>
        {{ $aside }}
    </aside>
    <div class="flex min-h-screen flex-col">
        <div class="flex items-center px-4 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 lg:invisible">
                <x-loop-logo class="h-9 w-9" />
                <span class="font-display text-xl font-semibold">Loop</span>
            </a>
        </div>
        <div class="flex flex-1 items-center px-4 pb-10 sm:px-8">
            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
