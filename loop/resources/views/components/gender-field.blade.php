@props([
    'value' => '',
])

<div>
    <p class="loop-label">{{ __('loop.gender') }}</p>
    <div class="mt-2 grid grid-cols-2 gap-3">
        <label class="flex cursor-pointer items-center justify-center rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm font-semibold has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft">
            <input type="radio" name="gender" value="male" class="sr-only" @checked(old('gender', $value) === 'male')>
            {{ __('loop.gender_male') }}
        </label>
        <label class="flex cursor-pointer items-center justify-center rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm font-semibold has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft">
            <input type="radio" name="gender" value="female" class="sr-only" @checked(old('gender', $value) === 'female')>
            {{ __('loop.gender_female') }}
        </label>
    </div>
</div>
