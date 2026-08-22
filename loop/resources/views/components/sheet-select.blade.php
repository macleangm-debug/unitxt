@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => '',
    'required' => false,
    'placeholder' => null,
    'autosubmit' => false,
    'searchPlaceholder' => null,
    'triggerClass' => null,
    'search' => null,
])

@php
    $fieldId = 'sheet-'.\Illuminate\Support\Str::slug($name);
    $placeholder = $placeholder ?? __('loop.pick_option');
    $searchPlaceholder = $searchPlaceholder ?? (str_contains($name, 'country') || $name === 'to'
        ? __('loop.search_country')
        : __('loop.search'));
    $triggerClass = $triggerClass ?: 'loop-input flex w-full items-center justify-between text-left';
    if ($label && $placeholder === $label) {
        $placeholder = __('loop.pick_option');
    }
    $optionsList = collect($options)->map(function ($optionLabel, $key) {
        if (is_array($optionLabel)) {
            return ['key' => (string) ($optionLabel['key'] ?? ''), 'label' => (string) ($optionLabel['label'] ?? '')];
        }

        return ['key' => (string) $key, 'label' => (string) $optionLabel];
    })->values()->all();
    $showSearch = $search === null ? count($optionsList) > 6 : (bool) $search;
@endphp

<div
    x-data="{
        open: false,
        value: @js(old($name, $value)),
        q: '',
        autosubmit: @js((bool) $autosubmit),
        options: @js($optionsList),
        get selectedLabel() {
            const hit = this.options.find(o => o.key === this.value);
            return hit ? hit.label : '';
        },
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.options;
            return this.options.filter(o => {
                const hay = (o.search || o.label || '').toLowerCase();
                return hay.includes(q);
            });
        },
        pick(key) {
            this.value = key;
            this.open = false;
            this.q = '';
            this.$dispatch('sheet-selected', { name: '{{ $name }}', value: key });
            if (this.autosubmit) {
                this.$nextTick(() => this.$root.closest('form')?.requestSubmit());
            }
        }
    }"
    x-effect="if (open) $nextTick(() => $refs.search?.focus())"
    class="relative"
    @keydown.escape.window="open = false"
>
    @if ($label)
        <label class="loop-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>
    <button type="button" id="{{ $fieldId }}" @click="open = true" class="{{ $triggerClass }}">
        <span x-text="selectedLabel || @js($placeholder)" :class="selectedLabel ? 'text-ink' : 'text-ink-muted'"></span>
        <span class="ml-2 shrink-0 text-violet">▾</span>
    </button>

    <x-picker-layer :title="$label ?? $placeholder" :search-placeholder="$searchPlaceholder" :search="$showSearch">
        <template x-for="opt in filtered" :key="'opt-'+opt.key">
            <button
                type="button"
                class="loop-picker-option"
                :class="{ 'is-selected': opt.key === value }"
                @click="pick(opt.key)"
            >
                <span x-text="opt.label"></span>
            </button>
        </template>
        <p x-show="filtered.length === 0" class="px-4 py-6 text-sm text-ink-muted">{{ __('loop.no_results') }}</p>
    </x-picker-layer>
</div>
