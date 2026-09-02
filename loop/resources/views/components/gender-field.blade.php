@props([
    'value' => '',
])

<x-sheet-select
    name="gender"
    :label="__('loop.gender')"
    :options="[
        'male' => __('loop.gender_male'),
        'female' => __('loop.gender_female'),
    ]"
    :value="old('gender', $value)"
    :required="true"
    :search="false"
    :placeholder="__('loop.gender')"
/>
