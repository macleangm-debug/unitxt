<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ $title }}</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<main class="loop-shell flex min-h-screen items-center py-12">
    <div class="mx-auto w-full max-w-lg">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <x-loop-logo class="h-9 w-9" />
            <span class="font-display text-xl font-semibold">Loop</span>
        </a>

        <div class="loop-status-card mt-8">
            <div class="loop-status-card__hero loop-status-card__hero--{{ $status >= 500 ? 'rejected' : 'pending' }} p-6 sm:p-8">
                <div class="loop-status-card__icon">{{ $status >= 500 ? '!' : $status }}</div>
                <p class="mt-4 text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.error_page_kicker') }}</p>
                <h1 class="mt-2 font-display text-3xl font-semibold">{{ $title }}</h1>
                <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ $body }}</p>
            </div>
            <div class="space-y-4 px-6 pb-6 sm:px-8 sm:pb-8">
                <div class="loop-confirm-steps !mt-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.error_response') }}</p>
                    <p class="mt-2 font-mono text-xs text-ink-muted">{{ $status }} · {{ $requestUrl }}</p>
                    <p class="mt-2 break-words text-sm font-medium text-ink">{{ $exception->getMessage() ?: class_basename($exception) }}</p>
                    @if ($debug)
                        <p class="mt-2 break-all font-mono text-[11px] text-ink-muted">{{ $exception->getFile() }}:{{ $exception->getLine() }}</p>
                    @endif
                    @if ($hit)
                        <p class="mt-3 text-xs text-ink-muted">{{ __('loop.error_ref') }}: <span class="font-mono">{{ $hit->fingerprint }}</span></p>
                    @endif
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <a href="{{ url()->previous('/') }}" class="loop-btn-ghost flex-1 justify-center">{{ __('loop.try_again') }}</a>
                    <a href="{{ url('/') }}" class="loop-btn-mint flex-1 justify-center">{{ __('loop.error_go_home') }}</a>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
