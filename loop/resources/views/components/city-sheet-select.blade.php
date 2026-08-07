@props([
    'name' => 'city',
    'label' => null,
    'value' => '',
    'cities' => [],
    'required' => false,
    'countryField' => 'country',
])

@php
    $fieldId = 'city-'.\Illuminate\Support\Str::slug($name);
    $citiesByCountry = collect(\App\Support\Countries::OPTIONS)
        ->mapWithKeys(fn ($meta, $code) => [$code => $meta['cities'] ?? []])
        ->all();
@endphp

<div
    x-data="{
        open: false,
        value: @js(old($name, $value)),
        country: @js(session('preferred_country', 'TZ')),
        citiesByCountry: @js($citiesByCountry),
        q: '',
        get cities() {
            return this.citiesByCountry[this.country] || [];
        },
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return this.cities;
            return this.cities.filter(c => c.toLowerCase().includes(q));
        },
        syncCountry() {
            const el = this.$root.closest('form')?.querySelector('[name={{ $countryField }}]');
            if (el) this.country = el.value;
        },
        pick(city) {
            this.value = city;
            this.open = false;
            this.q = '';
        }
    }"
    x-init="
        syncCountry();
        const form = $el.closest('form');
        form?.querySelector('[name={{ $countryField }}]')?.addEventListener('change', () => {
            syncCountry();
            if (!cities.includes(value)) value = '';
        });
    "
    class="relative"
>
    @if ($label)
        <label class="loop-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>
    <button type="button" id="{{ $fieldId }}" @click="syncCountry(); open = true" class="loop-input flex w-full items-center justify-between text-left">
        <span x-text="value || '{{ __('loop.pick_city') }}'" :class="value ? 'text-ink' : 'text-ink-muted'"></span>
        <span class="text-mint-deep">▾</span>
    </button>

    {{-- Desktop popover --}}
    <div
        x-show="open"
        x-cloak
        @keydown.escape.window="open=false"
        class="absolute z-30 mt-2 hidden max-h-72 w-full flex-col overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-[0_24px_60px_rgba(11,31,42,0.16)] sm:flex"
    >
        <div class="border-b border-ink/5 p-3">
            <input type="search" x-model="q" placeholder="{{ __('loop.search_city') }}" class="loop-input !py-2 text-sm" @click.stop>
        </div>
        <div class="overflow-y-auto p-2">
            <template x-for="city in filtered" :key="city">
                <button type="button" class="flex w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium hover:bg-mint-soft" :class="city === value ? 'bg-mint/20 font-semibold' : ''" @click="pick(city)" x-text="city"></button>
            </template>
            <p x-show="filtered.length === 0" class="px-3 py-4 text-sm text-ink-muted">{{ __('loop.no_cities') }}</p>
        </div>
    </div>

    {{-- Mobile bottom sheet --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 sm:hidden" @keydown.escape.window="open=false">
        <div class="absolute inset-0 bg-ink/40" @click="open=false"></div>
        <div class="absolute inset-x-0 bottom-0 max-h-[75vh] overflow-hidden rounded-t-3xl bg-white shadow-2xl" @click.stop>
            <div class="mx-auto mt-3 h-1 w-10 rounded-full bg-ink/15"></div>
            <div class="border-b border-ink/5 p-4">
                <p class="font-display text-base font-semibold">{{ $label ?? __('loop.city') }}</p>
                <input type="search" x-model="q" placeholder="{{ __('loop.search_city') }}" class="loop-input mt-3 !py-2 text-sm">
            </div>
            <div class="max-h-[50vh] overflow-y-auto p-2 pb-8">
                <template x-for="city in filtered" :key="city">
                    <button type="button" class="flex w-full rounded-xl px-3 py-3 text-left text-sm font-medium hover:bg-mint-soft" :class="city === value ? 'bg-mint/20 font-semibold' : ''" @click="pick(city)" x-text="city"></button>
                </template>
                <p x-show="filtered.length === 0" class="px-3 py-4 text-sm text-ink-muted">{{ __('loop.no_cities') }}</p>
            </div>
        </div>
    </div>
</div>
