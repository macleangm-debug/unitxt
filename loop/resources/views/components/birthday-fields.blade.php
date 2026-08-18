@props([
    'month' => '',
    'day' => '',
])

<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label class="loop-label" for="birth_month">{{ __('loop.month') }}</label>
        <select id="birth_month" name="birth_month" class="loop-input bg-white">
            <option value="">{{ __('loop.pick_option') }}</option>
            @for ($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected((string) old('birth_month', $month) === (string) $m)>{{ $m }}</option>
            @endfor
        </select>
    </div>
    <div>
        <label class="loop-label" for="birth_day">{{ __('loop.day') }}</label>
        <select id="birth_day" name="birth_day" class="loop-input bg-white">
            <option value="">{{ __('loop.pick_option') }}</option>
            @for ($d = 1; $d <= 31; $d++)
                <option value="{{ $d }}" @selected((string) old('birth_day', $day) === (string) $d)>{{ $d }}</option>
            @endfor
        </select>
    </div>
</div>
