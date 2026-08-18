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
    if ($label && $placeholder === $label) {
        $placeholder = __('loop.pick_option');
    }
    $optionsList = collect($options)->map(function ($optionLabel, $key) {
        if (is_array($optionLabel)) {
            return ['key' => (string) ($optionLabel['key'] ?? ''), 'label' => (string) ($optionLabel['label'] ?? '')];
        }

        return ['key' => (string) $key, 'label' => (string) $optionLabel];
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
        isDesktop() {
            return window.matchMedia('(min-width: 640px)').matches;
        },
        pick(key) {
            this.value = key;
            this.open = false;
            this.q = '';
            this.$dispatch('sheet-selected', { name: '{{ $name }}', value: key });
        }
    }"
    class="relative"
    @keydown.escape.window="open = false"
    @click.outside="if (isDesktop()) open = false"
>
    @if ($label)
        <label class="loop-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>
    <button type="button" id="{{ $fieldId }}" @click="open = !open" class="loop-input flex w-full items-center justify-between text-left">
        <span x-text="selectedLabel || @js($placeholder)" :class="selectedLabel ? 'text-ink' : 'text-ink-muted'"></span>
        <span class="text-violet">▾</span>
    </button>

    {{-- Desktop dropdown --}}
    <div
        x-show="open"
        x-cloak
        class="absolute z-30 mt-2 hidden max-h-72 w-full flex-col overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-[0_24px_60px_rgba(11,31,42,0.16)] sm:flex"
        @click.stop
        role="listbox"
    >
        <div class="border-b border-ink/5 p-3">
            <input type="search" x-model="q" placeholder="{{ __('loop.search') }}" class="loop-input !py-2 text-sm" autocomplete="off" @click.stop>
        </div>
        <div class="overflow-y-auto p-2">
            <template x-for="opt in filtered" :key="'desk-'+opt.key">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-medium transition hover:bg-violet-soft/60"
                    :class="opt.key === value ? 'bg-violet-soft font-semibold text-violet' : 'text-ink'"
                    @click="pick(opt.key)"
                >
                    <span x-text="opt.label"></span>
                    <span x-show="opt.key === value" class="text-violet">✓</span>
                </button>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-4 text-sm text-ink-muted">{{ __('loop.no_results') }}</p>
        </div>
    </div>

    {{-- Mobile bottom sheet --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[90] sm:hidden"
            @keydown.escape.window="open = false"
        >
            <div
                class="absolute inset-0 bg-ink/50 backdrop-blur-[2px]"
                x-show="open"
                x-transition.opacity.duration.200ms
                @click="open = false"
            ></div>
            <div
                class="absolute inset-x-0 bottom-0 flex max-h-[85vh] flex-col overflow-hidden rounded-t-[1.75rem] bg-white shadow-[0_-20px_60px_rgba(17,17,20,0.25)]"
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
                @click.stop
                role="dialog"
                aria-modal="true"
            >
                <div class="mx-auto mt-3 h-1.5 w-12 shrink-0 rounded-full bg-ink/15"></div>
                <div class="shrink-0 border-b border-ink/5 px-5 pb-3 pt-4">
                    <p class="font-display text-lg font-semibold">{{ $label ?? $placeholder }}</p>
                    <input type="search" x-model="q" placeholder="{{ __('loop.search') }}" class="loop-input mt-3 !py-2.5 text-sm" autocomplete="off">
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-1">
                    <template x-for="opt in filtered" :key="'mob-'+opt.key">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-2xl px-4 py-3.5 text-left text-sm font-medium transition hover:bg-violet-soft/60"
                            :class="opt.key === value ? 'bg-violet-soft font-semibold text-violet' : 'text-ink'"
                            @click="pick(opt.key)"
                        >
                            <span x-text="opt.label"></span>
                            <span x-show="opt.key === value" class="text-violet">✓</span>
                        </button>
                    </template>
                    <p x-show="filtered.length === 0" class="px-4 py-6 text-sm text-ink-muted">{{ __('loop.no_results') }}</p>
                </div>
            </div>
        </div>
    </template>
</div>
