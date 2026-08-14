@props([
    'href' => null,
    'label' => null,
])

@php
    $href = $href ?? route('settings');
    $label = $label ?? __('loop.back_to_settings');
@endphp

<a
    href="{{ $href }}"
    title="{{ $label }}"
    aria-label="{{ $label }}"
    {{ $attributes->class([
        'group inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-ink/10 bg-white/90 text-ink shadow-[0_8px_24px_rgba(17,17,20,0.04)] transition hover:border-violet/30 hover:bg-violet-soft/40 sm:h-auto sm:w-auto sm:gap-2 sm:px-3.5 sm:py-2.5 sm:text-sm sm:font-semibold',
    ]) }}
    @click="$store.loopNav.go(@js($href), $event, { kind: 'back' })"
>
    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-ink text-white transition group-hover:bg-violet sm:h-7 sm:w-7">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
    </span>
    <span class="hidden sm:inline">{{ $label }}</span>
</a>
