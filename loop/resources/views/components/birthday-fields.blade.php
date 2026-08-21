@props([
    'month' => '',
    'day' => '',
])

@php
    $month = old('birth_month', $month);
    $day = old('birth_day', $day);
    $monthValue = ($month === '' || $month === null) ? '' : (string) (int) $month;
    $dayValue = ($day === '' || $day === null) ? '' : (string) (int) $day;
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $months[(string) $m] = \Carbon\Carbon::createFromDate(2000, $m, 1)
            ->locale(app()->getLocale())
            ->translatedFormat('F');
    }
    $days = [];
    for ($d = 1; $d <= 31; $d++) {
        $days[(string) $d] = (string) $d;
    }
@endphp

<div class="grid gap-3 sm:grid-cols-2">
    <x-sheet-select
        name="birth_month"
        :label="__('loop.month')"
        :options="$months"
        :value="$monthValue"
        :placeholder="__('loop.month')"
    />
    <x-sheet-select
        name="birth_day"
        :label="__('loop.day')"
        :options="$days"
        :value="$dayValue"
        :placeholder="__('loop.day')"
    />
</div>
