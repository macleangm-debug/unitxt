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
    {{ $attributes->class([
        'group inline-flex items-center gap-2 rounded-2xl border border-ink/10 bg-white/90 px-3.5 py-2.5 text-sm font-semibold text-ink shadow-[0_8px_24px_rgba(17,17,20,0.04)] transition hover:border-violet/30 hover:bg-violet-soft/40',
    ]) }}
    @click="$store.loopNav.go(@js($href), $event, { kind: 'back' })"
>
    <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl bg-ink text-white transition group-hover:bg-violet">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
    </span>
    <span>{{ $label }}</span>
</a>
