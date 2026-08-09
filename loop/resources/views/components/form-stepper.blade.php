@props([
    'steps' => [],
])

@php
    /** @var array<int, string> $steps */
@endphp

<div class="mb-6 flex flex-wrap items-center gap-2">
    @foreach ($steps as $n => $label)
        <button
            type="button"
            @click="go({{ (int) $n }})"
            class="min-w-0 flex-1 rounded-2xl px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-[0.08em] transition sm:text-xs"
            :class="step === {{ (int) $n }} ? 'bg-ink text-white' : (step > {{ (int) $n }} ? 'bg-mint/25 text-ink' : 'bg-chalk text-ink-muted')"
        >
            <span class="block truncate">{{ (int) $n }}. {{ $label }}</span>
        </button>
    @endforeach
</div>

<p class="mb-4 text-sm text-ink-muted">
    {{ __('loop.step') }} <span class="font-semibold text-ink" x-text="step"></span>
    {{ __('loop.of') }} {{ count($steps) }}
</p>
