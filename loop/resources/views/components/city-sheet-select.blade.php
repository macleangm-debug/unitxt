@props([
    'name' => 'city',
    'label' => null,
    'value' => '',
    'cities' => [],
    'required' => false,
    'countryField' => 'country',
    'country' => null,
    'allowEmpty' => false,
    'emptyLabel' => null,
    'autosubmit' => false,
])

@php
    $fieldId = 'city-'.\Illuminate\Support\Str::slug($name);
    $emptyLabel = $emptyLabel ?? __('loop.all_cities');
    $citiesByCountry = collect(\App\Support\Countries::OPTIONS)
        ->mapWithKeys(fn ($meta, $code) => [$code => $meta['cities'] ?? []])
        ->all();
    $fixedCountry = $country;
    $staticCities = array_values(array_filter($cities, fn ($city) => is_string($city) && $city !== ''));
@endphp

<div
    x-data="{
        open: false,
        value: @js(old($name, $value)),
        country: @js($fixedCountry ?: session('preferred_country', 'TZ')),
        citiesByCountry: @js($citiesByCountry),
        staticCities: @js($staticCities),
        fixedCountry: @js($fixedCountry),
        allowEmpty: @js((bool) $allowEmpty),
        emptyLabel: @js($emptyLabel),
        autosubmit: @js((bool) $autosubmit),
        q: '',
        get cities() {
            const list = this.staticCities.length
                ? this.staticCities
                : (this.fixedCountry
                    ? (this.citiesByCountry[this.fixedCountry] || [])
                    : (this.citiesByCountry[this.country] || []));
            return this.allowEmpty ? ['', ...list] : list;
        },
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.cities;
            return this.cities.filter((c) => this.labelFor(c).toLowerCase().includes(q));
        },
        labelFor(city) {
            return city === '' ? this.emptyLabel : city;
        },
        syncCountry() {
            if (this.fixedCountry) {
                this.country = this.fixedCountry;
                return;
            }
            const el = this.$root.closest('form')?.querySelector('[name={{ $countryField }}]');
            if (el) this.country = el.value;
        },
        pick(city) {
            this.value = city;
            this.open = false;
            this.q = '';
            this.$dispatch('city-picked', { city });
            this.$dispatch('sheet-selected', { name: '{{ $name }}', value: city });
            if (this.autosubmit) {
                this.$nextTick(() => this.$root.closest('form')?.requestSubmit());
            }
        }
    }"
    x-init="
        syncCountry();
        const form = $el.closest('form');
        form?.addEventListener('sheet-selected', (e) => {
            if (e.detail?.name !== '{{ $countryField }}') return;
            country = e.detail.value || '';
            if (!cities.includes(value)) value = allowEmpty ? '' : '';
        });
        form?.querySelector('[name={{ $countryField }}]')?.addEventListener('change', () => {
            syncCountry();
            if (!cities.includes(value)) value = '';
        });
    "
    x-effect="if (open) { syncCountry(); $nextTick(() => $refs.search?.focus()) }"
    class="relative"
    @keydown.escape.window="open = false"
>
    @if ($label)
        <label class="loop-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>
    <button type="button" id="{{ $fieldId }}" @click="syncCountry(); open = true" class="loop-input flex w-full items-center justify-between text-left">
        <span x-text="value ? value : (allowEmpty ? emptyLabel : '{{ __('loop.pick_city') }}')" :class="value ? 'text-ink' : 'text-ink-muted'"></span>
        <span class="ml-2 shrink-0 text-violet">▾</span>
    </button>

    <x-picker-layer :title="$label ?? __('loop.city')" :search-placeholder="__('loop.search_city')">
        <template x-for="city in filtered" :key="'city-'+(city || 'all')">
            <button
                type="button"
                class="loop-picker-option"
                :class="{ 'is-selected': city === value }"
                @click="pick(city)"
            >
                <span x-text="labelFor(city)"></span>
            </button>
        </template>
        <p x-show="filtered.length === 0" class="px-4 py-6 text-sm text-ink-muted">{{ __('loop.no_cities') }}</p>
    </x-picker-layer>
</div>
