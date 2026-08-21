@props([
    'name' => 'sector',
    'value' => '',
    'required' => true,
    'otherName' => 'sector_other',
    'otherValue' => '',
])

@php
    $records = \App\Support\Sectors::pickerRecords();
    $featured = collect($records)->where('featured', true)->where('key', '!=', 'other')->values()->all();
    $categories = [];
    foreach ($records as $row) {
        if ($row['key'] === 'other') {
            continue;
        }
        $categories[$row['category']]['key'] = $row['category'];
        $categories[$row['category']]['label'] = \App\Support\Sectors::categoryLabel($row['category']);
        $categories[$row['category']]['items'][] = $row;
    }
    $other = collect($records)->firstWhere('key', 'other');
    $initial = old($name, $value);
@endphp

<div
    class="space-y-4"
    x-data="{
        open: false,
        value: @js($initial),
        q: '',
        reported: '',
        missUrl: @js(route('sector-search.miss')),
        options: @js($records),
        get selected() {
            return this.options.find(o => o.key === this.value) || null;
        },
        get selectedLabel() {
            return this.selected ? this.selected.label : '';
        },
        get filtered() {
            const q = this.q.trim().toLowerCase();
            if (!q) return [];
            return this.options.filter(o => o.key !== 'other' && (o.search || '').includes(q)).slice(0, 8);
        },
        get none() {
            return this.q.trim().length >= 2 && this.filtered.length === 0;
        },
        pick(key) {
            this.value = key;
            this.open = false;
            this.q = '';
            this.$dispatch('sheet-selected', { name: '{{ $name }}', value: key });
        },
        reportMiss() {
            const q = this.q.trim();
            if (!this.none || q === this.reported || q.length < 2) return;
            this.reported = q;
            const token = document.querySelector('meta[name=csrf-token]')?.content;
            fetch(this.missUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ q }),
            }).catch(() => {});
        }
    }"
    x-effect="if (none) { let t = setTimeout(() => reportMiss(), 700); return () => clearTimeout(t) }"
    @keydown.escape.window="open = false"
>
    <div>
        <p class="loop-label">{{ __('loop.what_kind_of_business') }}</p>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.search_business_type') }}</p>
        <div class="relative mt-3">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-ink-muted">🔍</span>
            <input
                type="search"
                x-model="q"
                class="loop-input pl-10"
                placeholder="{{ __('loop.search_sector_placeholder') }}"
                autocomplete="off"
                enterkeyhint="search"
            >
        </div>
        <input type="hidden" name="{{ $name }}" :value="value">
        <p class="mt-2 text-sm font-semibold text-violet" x-show="selectedLabel && !q" x-cloak x-text="selectedLabel"></p>
        <div class="mt-2 space-y-1" x-show="q.trim().length" x-cloak>
            <template x-for="opt in filtered" :key="'hit-'+opt.key">
                <button type="button" class="flex w-full rounded-2xl px-3 py-2.5 text-left text-sm font-semibold hover:bg-violet-soft/60" :class="opt.key === value ? 'bg-violet-soft text-violet' : 'text-ink'" @click="pick(opt.key)">
                    <span x-text="opt.label"></span>
                </button>
            </template>
            <div x-show="none" class="rounded-2xl bg-chalk px-3 py-3 text-sm text-ink-muted">
                <p>{{ __('loop.sector_no_match') }}</p>
                <button type="button" class="mt-2 font-semibold text-violet" @click="pick('other')">{{ __('loop.cant_find_business') }}</button>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach ($featured as $row)
            <button
                type="button"
                class="rounded-full border px-3 py-1.5 text-sm font-semibold"
                :class="value === @js($row['key']) ? 'border-violet bg-violet-soft text-violet' : 'border-ink/10 bg-white text-ink'"
                @click="pick(@js($row['key']))"
            >{{ $row['short'] ?: $row['label'] }}</button>
        @endforeach
    </div>

    <button type="button" class="text-sm font-semibold text-violet" @click="open = true">{{ __('loop.see_all_sectors') }}</button>

    <div x-show="value === 'other'" x-cloak class="mt-1">
        <label class="loop-label">{{ __('loop.other_sector') }}</label>
        <input name="{{ $otherName }}" value="{{ old($otherName, $otherValue) }}" class="loop-input" :required="value === 'other'">
        <x-input-error :messages="$errors->get($otherName)" class="mt-1" />
    </div>
    <x-input-error :messages="$errors->get($name)" class="mt-1" />

    <x-picker-layer :title="__('loop.what_kind_of_business')" :search-placeholder="__('loop.search_business_type')">
        @foreach ($categories as $group)
            <p class="sticky top-0 bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ $group['label'] }}</p>
            @foreach ($group['items'] as $row)
                <button
                    type="button"
                    class="loop-picker-option"
                    x-show="!q.trim() || @js($row['search']).includes(q.trim().toLowerCase())"
                    :class="{ 'is-selected': value === @js($row['key']) }"
                    @click="pick(@js($row['key']))"
                >{{ $row['label'] }}</button>
            @endforeach
        @endforeach
        @if ($other)
            <p class="sticky top-0 bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ \App\Support\Sectors::categoryLabel('other') }}</p>
            <button type="button" class="loop-picker-option" :class="{ 'is-selected': value === 'other' }" @click="pick('other')">{{ $other['label'] }}</button>
        @endif
        <p x-show="q.trim().length >= 2 && filtered.length === 0" class="px-4 py-4 text-sm text-ink-muted">{{ __('loop.sector_no_match') }}</p>
    </x-picker-layer>
</div>
