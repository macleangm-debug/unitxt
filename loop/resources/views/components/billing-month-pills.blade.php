@props([
    'discounts' => [],
])

<div class="loop-month-pills flex gap-2 overflow-x-auto pb-1" role="listbox" aria-label="{{ __('loop.how_long_continue') }}">
    @for ($month = 1; $month <= 12; $month++)
        @php $discount = (int) ($discounts[$month] ?? 0); @endphp
        <button
            type="button"
            role="option"
            @click="setMonths({{ $month }})"
            :aria-selected="months === {{ $month }}"
            :class="months === {{ $month }} ? 'border-violet bg-violet text-white' : 'border-ink/10 bg-white text-ink'"
            class="relative h-11 min-w-[2.75rem] shrink-0 rounded-full border px-3 text-sm font-semibold transition"
        >
            {{ $month }}
            @if ($month === 12)
                <span class="sr-only">{{ __('loop.interval_best_value') }}</span>
            @elseif ($discount > 0)
                <span class="sr-only">{{ $discount }}%</span>
            @endif
        </button>
    @endfor
</div>
