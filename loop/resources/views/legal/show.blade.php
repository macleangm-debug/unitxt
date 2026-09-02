@php
    $inner = true;
@endphp
@if ($inApp ?? false)
    <x-app-layout>
        <x-slot name="header">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.legal_privacy') }}</p>
            <h1 class="loop-page-title font-display text-3xl font-semibold">{{ $document->title() }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.legal_version_date', ['version' => $document->version, 'date' => optional($document->effective_on)->format('d M Y')]) }}</p>
        </x-slot>
        @include('legal.partials.document')
    </x-app-layout>
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $document->title() }} · Loop</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<div class="min-h-screen bg-chalk">
    <x-site-header />
    <div class="loop-shell py-10">
        <a href="{{ route('legal.index') }}" class="text-sm font-semibold text-violet">← {{ __('loop.legal_privacy') }}</a>
        <h1 class="mt-4 font-display text-3xl font-semibold">{{ $document->title() }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.legal_version_date', ['version' => $document->version, 'date' => optional($document->effective_on)->format('d M Y')]) }}</p>
        @include('legal.partials.document')
    </div>
    <x-site-footer />
</div>
</body>
</html>
@endif
