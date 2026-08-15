@props([
    'title' => null,
])

@php
    $title = $title ?? __('loop.filters');
@endphp

<div
    x-data="{ open: false }"
    class="{{ $attributes->get('class') }}"
>
    <button type="button" class="loop-btn-ghost inline-flex items-center gap-2 !py-2.5" @click="open = true">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4" />
        </svg>
        {{ $title }}
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[90]"
            @keydown.escape.window="open = false"
        >
            <div
                class="absolute inset-0 bg-ink/50 backdrop-blur-[2px]"
                x-show="open"
                x-transition.opacity.duration.200ms
                @click="open = false"
            ></div>
            <div
                class="absolute inset-x-0 bottom-0 flex max-h-[88vh] flex-col overflow-hidden rounded-t-[1.75rem] bg-white shadow-[0_-20px_60px_rgba(17,17,20,0.25)] sm:inset-x-auto sm:bottom-auto sm:left-1/2 sm:top-1/2 sm:w-full sm:max-w-md sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-[1.75rem]"
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full sm:translate-y-0 sm:opacity-0 sm:scale-95"
                x-transition:enter-end="translate-y-0 sm:opacity-100 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0 sm:opacity-100 sm:scale-100"
                x-transition:leave-end="translate-y-full sm:translate-y-0 sm:opacity-0 sm:scale-95"
                @click.stop
                role="dialog"
                aria-modal="true"
            >
                <div class="mx-auto mt-3 h-1.5 w-12 shrink-0 rounded-full bg-ink/15 sm:hidden"></div>
                <div class="flex items-center justify-between border-b border-ink/5 px-5 pb-3 pt-4">
                    <p class="font-display text-lg font-semibold">{{ $title }}</p>
                    <button type="button" class="rounded-xl bg-chalk px-3 py-1.5 text-xs font-semibold text-ink-muted" @click="open = false">{{ __('loop.done') }}</button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-4 pb-[max(1.5rem,env(safe-area-inset-bottom))]">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>
