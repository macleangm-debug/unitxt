<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="admin-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Permissions-Policy" content="notifications=(), push=()">
    <title>{{ $title ?? (($header ?? null) ? strip_tags($header) : __('loop.admin')).' · '.config('app.name', 'Loop') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="admin-body font-sans" x-data="{ navOpen: false }">
@php $user = auth()->user(); @endphp
<div class="admin-shell admin-console">
    @include('admin.partials.sidebar')
    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-icon-btn lg:hidden" @click="navOpen = true" aria-label="{{ __('loop.menu') }}">☰</button>
            <div class="min-w-0 flex-1">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <span class="hidden text-sm text-slate-500 sm:inline">{{ $user?->full_phone ?? $user?->phone }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="admin-btn-ghost">{{ __('loop.log_out') }}</button>
                </form>
            </div>
        </header>
        <div class="admin-content">
            @if (session('status') && ! session('all_set') && ! session('confirm'))
                <div class="admin-flash">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>

@php
    $confirm = session('confirm');
@endphp
@include('partials.confirm-modal', ['confirm' => $confirm])
</body>
</html>
