@props([
    'name' => 'district',
    'label' => null,
    'value' => '',
    'required' => false,
    'cityField' => 'city',
    'city' => null,
])

@php
    $fieldId = 'district-'.\Illuminate\Support\Str::slug($name);
    $districtsByCity = collect(\App\Support\Countries::OPTIONS)
        ->flatMap(fn ($meta) => $meta['cities'] ?? [])
        ->unique()
        ->mapWithKeys(fn ($cityName) => [$cityName => \App\Support\Countries::districts($cityName)])
        ->all();
@endphp

<div
    x-data="{
        open: false,
        value: @js(old($name, $value)),
        city: @js($city),
        districtsByCity: @js($districtsByCity),
        q: '',
        get districts() {
            if (this.city && this.districtsByCity[this.city]) return this.districtsByCity[this.city];
            return this.city ? [this.city] : [];
        },
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.districts;
            return this.districts.filter(d => d.toLowerCase().includes(q));
        },
        syncCity() {
            const el = this.$root.closest('form')?.querySelector('[name={{ $cityField }}]');
            if (el) this.city = el.value;
        },
        pick(district) {
            this.value = district;
            this.open = false;
            this.q = '';
            this.$dispatch('sheet-selected', { name: '{{ $name }}', value: district });
        }
    }"
    x-init="
        syncCity();
        const form = $el.closest('form');
        form?.addEventListener('city-picked', (e) => {
            city = e.detail?.city || '';
            if (!districts.includes(value)) value = '';
        });
        form?.addEventListener('sheet-selected', (e) => {
            if (e.detail?.name !== '{{ $cityField }}') return;
            city = e.detail.value || '';
            if (!districts.includes(value)) value = '';
        });
        form?.querySelector('[name={{ $cityField }}]')?.addEventListener('change', () => {
            syncCity();
            if (!districts.includes(value)) value = '';
        });
    "
    x-effect="if (open) { syncCity(); $nextTick(() => $refs.search?.focus()) }"
    class="relative"
    @keydown.escape.window="open = false"
>
    @if ($label)
        <label class="loop-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>
    <button type="button" id="{{ $fieldId }}" @click="syncCity(); open = true" class="loop-input flex w-full items-center justify-between text-left">
        <span x-text="value || '{{ __('loop.pick_district') }}'" :class="value ? 'text-ink' : 'text-ink-muted'"></span>
        <span class="ml-2 shrink-0 text-violet">▾</span>
    </button>

    <x-picker-layer :title="$label ?? __('loop.district')" :search-placeholder="__('loop.search_district')">
        <template x-for="district in filtered" :key="'d-'+district">
            <button
                type="button"
                class="loop-picker-option"
                :class="{ 'is-selected': district === value }"
                @click="pick(district)"
            >
                <span x-text="district"></span>
            </button>
        </template>
        <p x-show="filtered.length === 0" class="px-4 py-6 text-sm text-ink-muted">{{ __('loop.no_districts') }}</p>
    </x-picker-layer>
</div>
