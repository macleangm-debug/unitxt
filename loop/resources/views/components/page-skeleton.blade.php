@props([
    'variant' => 'app',
])

@php
    $variant = in_array($variant, ['app', 'admin', 'guest', 'public'], true) ? $variant : 'app';
@endphp

<div
    class="loop-page-skeleton"
    data-variant="{{ $variant }}"
    role="status"
    aria-live="polite"
    aria-label="{{ __('loop.page_loading') }}"
>
    @if ($variant === 'admin')
        <div class="loop-skel-admin">
            <div class="loop-skel-admin__rail">
                <span class="loop-skel-bone loop-skel-bone--logo"></span>
                <span class="loop-skel-bone loop-skel-line"></span>
                <span class="loop-skel-bone loop-skel-line"></span>
                <span class="loop-skel-bone loop-skel-line"></span>
                <span class="loop-skel-bone loop-skel-line"></span>
                <span class="loop-skel-bone loop-skel-line"></span>
            </div>
            <div class="loop-skel-admin__main">
                <span class="loop-skel-bone loop-skel-bar"></span>
                <div class="loop-skel-stats">
                    <span class="loop-skel-bone loop-skel-stat"></span>
                    <span class="loop-skel-bone loop-skel-stat"></span>
                    <span class="loop-skel-bone loop-skel-stat"></span>
                    <span class="loop-skel-bone loop-skel-stat"></span>
                </div>
                <span class="loop-skel-bone loop-skel-panel"></span>
            </div>
        </div>
    @elseif ($variant === 'guest')
        <div class="loop-skel-guest">
            <div class="loop-skel-guest__aside">
                <span class="loop-skel-bone loop-skel-bone--logo loop-skel-bone--on-ink"></span>
                <span class="loop-skel-bone loop-skel-title loop-skel-bone--on-ink"></span>
                <span class="loop-skel-bone loop-skel-copy loop-skel-bone--on-ink"></span>
            </div>
            <div class="loop-skel-guest__form">
                <span class="loop-skel-bone loop-skel-title"></span>
                <span class="loop-skel-bone loop-skel-input"></span>
                <span class="loop-skel-bone loop-skel-input"></span>
                <span class="loop-skel-bone loop-skel-btn"></span>
            </div>
        </div>
    @elseif ($variant === 'public')
        <div class="loop-skel-public">
            <span class="loop-skel-bone loop-skel-bar"></span>
            <div class="loop-skel-public__hero">
                <span class="loop-skel-bone loop-skel-display"></span>
                <span class="loop-skel-bone loop-skel-copy"></span>
                <div class="loop-skel-public__cards">
                    <span class="loop-skel-bone loop-skel-card"></span>
                    <span class="loop-skel-bone loop-skel-card"></span>
                </div>
            </div>
        </div>
    @else
        <div class="loop-skel-app">
            <span class="loop-skel-bone loop-skel-bar"></span>
            <div class="loop-skel-app__body">
                <span class="loop-skel-bone loop-skel-hero"></span>
                <div class="loop-skel-stats">
                    <span class="loop-skel-bone loop-skel-stat"></span>
                    <span class="loop-skel-bone loop-skel-stat"></span>
                </div>
                <span class="loop-skel-bone loop-skel-row"></span>
                <span class="loop-skel-bone loop-skel-row"></span>
                <span class="loop-skel-bone loop-skel-row"></span>
            </div>
        </div>
    @endif
    <span class="sr-only">{{ __('loop.page_loading') }}</span>
</div>
