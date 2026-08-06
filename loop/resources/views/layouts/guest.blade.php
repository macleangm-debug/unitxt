<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Loop') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink antialiased">
<div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
    <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3">
        <x-loop-logo class="h-12 w-12" />
        <span class="font-display text-3xl font-semibold tracking-tight">Loop</span>
    </a>
    <div class="w-full max-w-md loop-panel px-6 py-7">{{ $slot }}</div>
</div>
</body>
</html>
