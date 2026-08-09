@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => '',
    'required' => false,
    'placeholder' => null,
])

@php
    $fieldId = 'sheet-'.\Illuminate\Support\Str::slug($name);
    $placeholder = $placeholder ?? __('loop.pick_option');
    $optionsList = collect($options)->map(function ($label, $key) {
        if (is_array($label)) {
            return ['key' => (string) ($label['key'] ?? ''), 'label' => (string) ($label['label'] ?? '')];
        }

        return ['key' => (string) $key, 'label' => (string) $label];
    })->values()->all();
@endphp

<div
    x-data="{
        open: false,
        value: @js(old($name, $value)),
        q: '',
        options: @js($optionsList),
        get selectedLabel() {
            const hit = this.options.find(o => o.key === this.value);
            return hit ? hit.label : '';
        },
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.options;
            return this.options.filter(o => o.label.toLowerCase().includes(q));
        },
        pick(key) {
            this.value = key;
            this.open = false;
            this.q = '';
            this.$dispatch('sheet-selected', { name: '{{ $name }}', value: key });
        }
    }"
    class="relative"
>
    @if ($label)
        <label class="loop-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>
    <button type="button" id="{{ $fieldId }}" @click="open = true" class="loop-input flex w-full items-center justify-between text-left">
        <span x-text="selectedLabel || @js($placeholder)" :class="selectedLabel ? 'text-ink' : 'text-ink-muted'"></span>
        <span class="text-mint-deep">▾</span>
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="open=false">
        <div class="absolute inset-0 bg-ink/45" @click="open=false"></div>
        <div class="absolute inset-x-0 bottom-0 max-h-[80vh] overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:inset-x-auto sm:bottom-auto sm:left-1/2 sm:top-1/2 sm:w-full sm:max-w-md sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-3xl" @click.stop>
            <div class="mx-auto mt-3 h-1 w-10 rounded-full bg-ink/15 sm:hidden"></div>
            <div class="border-b border-ink/5 p-4">
                <p class="font-display text-lg font-semibold">{{ $label ?? $placeholder }}</p>
                <input type="search" x-model="q" placeholder="{{ __('loop.search') }}" class="loop-input mt-3 !py-2 text-sm">
            </div>
            <div class="max-h-[55vh] overflow-y-auto p-2 pb-8">
                <template x-for="opt in filtered" :key="opt.key">
                    <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-3 text-left text-sm font-medium hover:bg-mint-soft" :class="opt.key === value ? 'bg-mint-soft font-semibold text-mint-deep' : 'text-ink'" @click="pick(opt.key)">
                        <span x-text="opt.label"></span>
                        <span x-show="opt.key === value" class="text-mint-deep">✓</span>
                    </button>
                </template>
                <p x-show="filtered.length === 0" class="px-3 py-4 text-sm text-ink-muted">{{ __('loop.no_results') }}</p>
            </div>
        </div>
    </div>
</div>
