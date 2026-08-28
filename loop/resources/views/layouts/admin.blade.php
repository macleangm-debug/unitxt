<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="admin-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Permissions-Policy" content="notifications=(), push=()">
    <title>{{ $title ?? (($header ?? null) ? strip_tags($header) : __('loop.admin')).' · '.config('app.name', 'Loop') }}</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|ibm-plex-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body font-sans" x-data="{ navOpen: false, inboxOpen: false }">
<x-page-skeleton variant="admin" />
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
                <x-admin.locale-switch />
                <div class="relative" @click.outside="inboxOpen = false">
                    <button
                        type="button"
                        class="admin-bell"
                        @click="inboxOpen = !inboxOpen"
                        aria-label="{{ __('loop.notifications') }}"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/>
                        </svg>
                        @if (($adminInboxCount ?? 0) > 0)
                            <span class="admin-bell__badge">{{ $adminInboxCount > 9 ? '9+' : $adminInboxCount }}</span>
                        @endif
                    </button>
                    <div x-show="inboxOpen" x-cloak class="admin-inbox">
                        <p class="admin-inbox__title">{{ __('loop.notifications') }}</p>
                        @forelse ($adminInbox ?? [] as $item)
                            <a href="{{ $item['url'] }}" class="admin-inbox__item" @click="inboxOpen = false">
                                <span class="font-semibold">{{ $item['title'] }}</span>
                                <span class="mt-0.5 block text-xs text-slate-500">{{ $item['body'] }}</span>
                                <span class="mt-2 inline-flex text-xs font-semibold text-violet">{{ $item['cta'] }} →</span>
                            </a>
                        @empty
                            <p class="px-3 py-4 text-sm text-slate-500">{{ __('loop.admin_inbox_empty') }}</p>
                        @endforelse
                    </div>
                </div>
                <span class="hidden text-sm text-slate-500 sm:inline">{{ $user?->full_phone ?? $user?->phone }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="admin-btn-ghost">{{ __('loop.log_out') }}</button>
                </form>
            </div>
        </header>
        <div class="admin-content">
            {{ $slot }}
        </div>
    </div>
</div>

@php
    $confirm = \App\Support\Confirm::resolve(session('confirm'), session('status'), $errors ?? null);
@endphp
@include('partials.admin-confirm-modal', ['confirm' => $confirm])
</body>
</html>
