@props([
    'name',
    'label' => null,
    'value' => '',
    'required' => false,
    'optional' => false,
    'min' => null,
])

@php
    $fieldId = 'date-'.\Illuminate\Support\Str::slug($name);
@endphp

<div
    class="relative"
    x-data="loopDateField({
        value: @js($value ?: ''),
        placeholder: @js($optional ? __('loop.optional_date') : __('loop.pick_date')),
        min: @js($min ?: ''),
    })"
    @keydown.escape.window="close()"
    @click.outside="close()"
>
    @if ($label)
        <label class="loop-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>
    <button
        type="button"
        id="{{ $fieldId }}"
        @click="toggle()"
        class="loop-input flex w-full items-center justify-between text-left"
        :aria-expanded="open"
    >
        <span x-text="display() || placeholder" :class="value ? 'text-ink' : 'text-ink-muted'"></span>
        <span class="text-mint-deep">▾</span>
    </button>

    {{-- Desktop popover: x-show owns visibility. max-sm:hidden keeps the sheet for phones. --}}
    <div
        x-cloak
        x-show.important="open"
        class="absolute z-30 mt-2 w-80 overflow-hidden rounded-2xl border border-ink/10 bg-white p-4 shadow-[0_24px_60px_rgba(11,31,42,0.16)] max-sm:hidden"
    >
        <div class="mb-3 flex items-center justify-between">
            <button type="button" class="rounded-lg px-2 py-1 text-sm font-semibold hover:bg-chalk disabled:text-ink/25" @click="prev" :disabled="!canPrev()">‹</button>
            <p class="font-display text-sm font-semibold" x-text="monthLabel()"></p>
            <button type="button" class="rounded-lg px-2 py-1 text-sm font-semibold hover:bg-chalk" @click="next">›</button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-ink-muted">
            <template x-for="w in weekdays" :key="w"><div x-text="w"></div></template>
        </div>
        <div class="mt-1 grid grid-cols-7 gap-1">
            <template x-for="(day, idx) in days()" :key="idx">
                <button
                    type="button"
                    class="aspect-square rounded-xl text-sm font-semibold transition"
                    :class="isSelected(day) ? 'bg-mint text-ink' : (isDisabled(day) ? 'cursor-not-allowed text-ink/25' : (day ? 'hover:bg-mint-soft text-ink' : 'invisible'))"
                    :disabled="isDisabled(day)"
                    @click="pick(day)"
                    x-text="day || ''"
                ></button>
            </template>
        </div>
        <div class="mt-3 flex justify-between">
            @if ($optional)
                <button type="button" class="text-xs font-semibold text-ink-muted" @click="clear">{{ __('loop.clear') }}</button>
            @else
                <span></span>
            @endif
            <button type="button" class="text-xs font-semibold text-mint-deep" @click="close()">{{ __('loop.done') }}</button>
        </div>
    </div>

    <x-loop-sheet
        :title="$label ?? __('loop.pick_date')"
        model="open"
        show="open && isCompact"
    >
        <div class="mb-3 flex items-center justify-between">
            <button type="button" class="rounded-lg px-2 py-1 text-sm font-semibold disabled:text-ink/25" @click="prev" :disabled="!canPrev()">‹</button>
            <p class="font-display text-base font-semibold" x-text="monthLabel()"></p>
            <button type="button" class="rounded-lg px-2 py-1 text-sm font-semibold" @click="next">›</button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-ink-muted">
            <template x-for="w in weekdays" :key="w"><div x-text="w"></div></template>
        </div>
        <div class="mt-1 grid grid-cols-7 gap-1">
            <template x-for="(day, idx) in days()" :key="idx">
                <button
                    type="button"
                    class="aspect-square rounded-xl text-sm font-semibold"
                    :class="isSelected(day) ? 'bg-mint text-ink' : (isDisabled(day) ? 'cursor-not-allowed text-ink/25' : (day ? 'hover:bg-mint-soft text-ink' : 'invisible'))"
                    :disabled="isDisabled(day)"
                    @click="pick(day)"
                    x-text="day || ''"
                ></button>
            </template>
        </div>
        <div class="mt-4 flex gap-3">
            @if ($optional)
                <button type="button" class="loop-btn-ghost flex-1" @click="clear">{{ __('loop.clear') }}</button>
            @endif
            <button type="button" class="loop-btn-mint flex-1" @click="close()">{{ __('loop.done') }}</button>
        </div>
    </x-loop-sheet>
</div>
