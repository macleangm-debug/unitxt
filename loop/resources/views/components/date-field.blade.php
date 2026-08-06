@props([
    'name',
    'label' => null,
    'value' => '',
    'required' => false,
    'optional' => false,
])

@php
    $fieldId = 'date-'.\Illuminate\Support\Str::slug($name);
@endphp

<div
    x-data="{
        open: false,
        value: @js($value),
        view: (() => {
            const base = @js($value) || new Date().toISOString().slice(0,10);
            const [y,m] = base.split('-').map(Number);
            return { year: y, month: m };
        })(),
        weekdays: ['Mo','Tu','We','Th','Fr','Sa','Su'],
        monthLabel() {
            return new Date(this.view.year, this.view.month - 1, 1).toLocaleString(undefined, { month: 'long', year: 'numeric' });
        },
        days() {
            const first = new Date(this.view.year, this.view.month - 1, 1);
            let start = (first.getDay() + 6) % 7;
            const daysInMonth = new Date(this.view.year, this.view.month, 0).getDate();
            const cells = [];
            for (let i = 0; i < start; i++) cells.push(null);
            for (let d = 1; d <= daysInMonth; d++) cells.push(d);
            return cells;
        },
        pick(day) {
            if (!day) return;
            const mm = String(this.view.month).padStart(2,'0');
            const dd = String(day).padStart(2,'0');
            this.value = `${this.view.year}-${mm}-${dd}`;
            this.open = false;
        },
        display() {
            if (!this.value) return '';
            const [y,m,d] = this.value.split('-');
            return `${d}/${m}/${y}`;
        },
        prev() {
            if (this.view.month === 1) { this.view.month = 12; this.view.year--; }
            else this.view.month--;
        },
        next() {
            if (this.view.month === 12) { this.view.month = 1; this.view.year++; }
            else this.view.month++;
        },
        clear() { this.value = ''; this.open = false; }
    }"
    class="relative"
>
    @if ($label)
        <label class="loop-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif
    <input type="hidden" name="{{ $name }}" :value="value" @if($required) required @endif>
    <button type="button" id="{{ $fieldId }}" @click="open = true" class="loop-input flex w-full items-center justify-between text-left">
        <span x-text="display() || '{{ $optional ? __('loop.optional_date') : __('loop.pick_date') }}'" :class="value ? 'text-ink' : 'text-ink-muted'"></span>
        <span class="text-mint-deep">▾</span>
    </button>

    {{-- Desktop popover --}}
    <div
        x-show="open"
        x-cloak
        @keydown.escape.window="open=false"
        class="absolute z-30 mt-2 hidden w-80 overflow-hidden rounded-2xl border border-ink/10 bg-white p-4 shadow-[0_24px_60px_rgba(11,31,42,0.16)] sm:block"
    >
        <div class="mb-3 flex items-center justify-between">
            <button type="button" class="rounded-lg px-2 py-1 text-sm font-semibold hover:bg-chalk" @click="prev">‹</button>
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
                    :class="day && value === `${view.year}-${String(view.month).padStart(2,'0')}-${String(day).padStart(2,'0')}` ? 'bg-mint text-ink' : (day ? 'hover:bg-mint-soft text-ink' : 'invisible')"
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
            <button type="button" class="text-xs font-semibold text-mint-deep" @click="open=false">{{ __('loop.done') }}</button>
        </div>
    </div>

    {{-- Mobile bottom sheet --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-40 sm:hidden" @keydown.escape.window="open=false">
        <div class="absolute inset-0 bg-ink/40" @click="open=false"></div>
        <div class="absolute inset-x-0 bottom-0 rounded-t-3xl bg-white p-5 pb-8 shadow-2xl" @click.stop>
            <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-ink/15"></div>
            <div class="mb-3 flex items-center justify-between">
                <button type="button" class="rounded-lg px-2 py-1 text-sm font-semibold" @click="prev">‹</button>
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
                        :class="day && value === `${view.year}-${String(view.month).padStart(2,'0')}-${String(day).padStart(2,'0')}` ? 'bg-mint text-ink' : (day ? 'hover:bg-mint-soft text-ink' : 'invisible')"
                        @click="pick(day)"
                        x-text="day || ''"
                    ></button>
                </template>
            </div>
            <div class="mt-4 flex gap-3">
                @if ($optional)
                    <button type="button" class="loop-btn-ghost flex-1" @click="clear">{{ __('loop.clear') }}</button>
                @endif
                <button type="button" class="loop-btn-mint flex-1" @click="open=false">{{ __('loop.done') }}</button>
            </div>
        </div>
    </div>
</div>
