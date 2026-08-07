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
    <aside class="relative hidden overflow-hidden bg-ink lg:flex lg:flex-col lg:justify-between lg:p-12">
        <div class="pointer-events-none absolute -left-10 top-20 h-64 w-64 rounded-full bg-mint/25 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-10 right-0 h-72 w-72 rounded-full bg-coral/20 blur-3xl"></div>
        <div class="relative">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-white">
                <x-loop-logo class="h-10 w-10" />
                <span class="font-display text-2xl font-semibold">Loop</span>
            </a>
            <div class="mt-12 inline-flex rotate-[-6deg] items-center gap-2 rounded-2xl border border-mint/40 bg-white/5 px-4 py-3 text-mint shadow-[0_0_40px_rgba(45,212,168,0.15)]">
                <span class="font-display text-sm font-semibold uppercase tracking-[0.2em]">{{ $asideStamp ?? __('loop.customer_stamp') }}</span>
            </div>
            <p class="mt-8 font-display text-4xl font-semibold leading-tight text-white">{{ $asideTitle ?? __('loop.auth_aside_title') }}</p>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/70">{{ $asideBody ?? __('loop.auth_aside_body') }}</p>
        </div>
        <div class="relative mt-10 space-y-3 text-sm text-white/65">
            <p>◆ {{ $asidePoint1 ?? __('loop.auth_aside_1') }}</p>
            <p>◆ {{ $asidePoint2 ?? __('loop.auth_aside_2') }}</p>
            <p>◆ {{ $asidePoint3 ?? __('loop.auth_aside_3') }}</p>
        </div>
    </aside>

    <div class="flex min-h-screen flex-col bg-[linear-gradient(135deg,#e8f7f1_0%,#f7f4ef_45%,#fff_100%)]">
        <div class="flex items-center justify-between gap-3 px-4 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 lg:invisible">
                <x-loop-logo class="h-9 w-9" />
                <span class="font-display text-xl font-semibold">Loop</span>
            </a>
            <div class="ml-auto flex items-center gap-2">
                <form method="POST" action="{{ route('preference.country') }}">
                    @csrf
                    <select name="country" onchange="this.form.submit()" class="rounded-xl border border-ink/10 bg-white px-2.5 py-1.5 text-xs font-semibold">
                        @foreach (\App\Support\Countries::OPTIONS as $code => $meta)
                            <option value="{{ $code }}" @selected(session('preferred_country', 'TZ') === $code)>{{ $meta['flag'] }} {{ $code }}</option>
                        @endforeach
                    </select>
                </form>
                <div class="flex rounded-xl border border-ink/10 bg-white p-0.5 text-xs font-semibold">
                    <a href="{{ route('locale', 'en') }}" class="rounded-lg px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-ink text-white' : 'text-ink-muted' }}">EN</a>
                    <a href="{{ route('locale', 'sw') }}" class="rounded-lg px-2 py-1 {{ app()->getLocale() === 'sw' ? 'bg-ink text-white' : 'text-ink-muted' }}">SW</a>
                </div>
            </div>
        </div>
        <div class="flex flex-1 items-center px-4 pb-10 sm:px-8">
            <div class="mx-auto w-full max-w-md loop-panel px-6 py-7">{{ $slot }}</div>
        </div>
    </div>
</div>
@include('partials.confirm-modal')
</body>
</html>
