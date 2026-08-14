@props([
    'steps' => [],
])

@php
    /** @var array<int, string> $steps */
@endphp

{{-- Onboarding-style progress lines + labels --}}
<div class="mb-5">
    <div class="flex gap-2">
        @foreach ($steps as $n => $label)
            <button
                type="button"
                @click="go({{ (int) $n }})"
                class="h-1.5 flex-1 rounded-full transition"
                :class="step >= {{ (int) $n }} ? 'bg-mint-deep' : 'bg-ink/10'"
                :aria-current="step === {{ (int) $n }} ? 'step' : null"
                :title="@js($label)"
            ></button>
        @endforeach
    </div>
    <div class="mt-3 flex gap-2">
        @foreach ($steps as $n => $label)
            <button
                type="button"
                @click="go({{ (int) $n }})"
                class="min-w-0 flex-1 text-center text-[10px] font-semibold uppercase tracking-[0.1em] transition sm:text-[11px]"
                :class="step === {{ (int) $n }} ? 'text-ink' : (step > {{ (int) $n }} ? 'text-mint-deep' : 'text-ink-muted')"
            >
                <span class="block truncate">{{ (int) $n }}. {{ $label }}</span>
            </button>
        @endforeach
    </div>
    <p class="mt-2 text-center text-xs font-semibold text-ink-muted">
        {{ __('loop.step') }}
        <span class="text-ink" x-text="typeof step === 'undefined' ? 1 : step">1</span>/{{ count($steps) }}
    </p>
</div>
