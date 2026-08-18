@php
    $registerSteps = [
        1 => __('loop.member_intro'),
        2 => __('loop.interests'),
        3 => __('loop.create_pin'),
    ];
    $errorStep = 1;
    if ($errors->hasAny(['interests'])) {
        $errorStep = 2;
    } elseif ($errors->hasAny(['pin', 'pin_confirmation'])) {
        $errorStep = 3;
    }
    $initialStep = $errors->any() ? $errorStep : (int) old('_step', 1);
@endphp
<x-guest-layout
    :aside-title="__('loop.customer_aside_title')"
    :aside-body="__('loop.customer_aside_body')"
    :aside-stamp="__('loop.customer_stamp')"
    :aside-point1="__('loop.customer_aside_1')"
    :aside-point2="__('loop.customer_aside_2')"
    :aside-point3="__('loop.customer_aside_3')"
>
    <div
        x-data="memberRegisterWizard({ step: {{ (int) $initialStep }}, force: {{ $errors->any() ? 'true' : 'false' }} })"
        x-effect="persist()"
    >
        <div class="mb-4">
            <h1 class="font-display text-2xl font-semibold">
                {{ $existing ? __('Finish your Loop') : __('Create your Loop') }}
            </h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $auth['country_code'] }} {{ $auth['phone'] }}</p>
        </div>

        <x-form-stepper :steps="$registerSteps" />

        <form
            x-ref="form"
            method="POST"
            action="{{ route('customer.register.store') }}"
            class="space-y-4"
            autocomplete="off"
            @submit="submitForm($event)"
        >
            @csrf
            <input type="hidden" name="_step" :value="step">

            <div data-step="1" x-show="step === 1" class="space-y-4">
                <p class="text-sm text-ink-muted">{{ __('loop.member_intro_blurb') }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="loop-label">{{ __('loop.first_name') }}</label>
                        <input name="first_name" value="{{ old('first_name', $existing?->first_name) }}" class="loop-input" :required="step === 1">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.last_name') }}</label>
                        <input name="last_name" value="{{ old('last_name', $existing?->last_name) }}" class="loop-input" :required="step === 1">
                    </div>
                </div>

                <div>
                    <label class="loop-label">{{ __('loop.country') }}</label>
                    <select name="country" class="loop-input" :required="step === 1">
                        @foreach ($countries as $code => $meta)
                            <option value="{{ $code }}" @selected(old('country', $country) === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="loop-label">{{ __('loop.city') }}</label>
                    <input name="city" list="city-list" value="{{ old('city', $existing?->city ?? ($cities[0] ?? '')) }}" class="loop-input" :required="step === 1">
                    <datalist id="city-list">
                        @foreach ($cities as $cityOption)
                            <option value="{{ $cityOption }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div>
                    <p class="loop-label">{{ __('loop.birthday') }}</p>
                    <div class="mt-1">
                        <x-birthday-fields :month="old('birth_month', $existing?->birth_month)" :day="old('birth_day', $existing?->birth_day)" />
                    </div>
                </div>

                <x-gender-field :value="old('gender', $existing?->gender)" />

                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <p class="text-sm text-ink-muted">{{ __('loop.member_interests_blurb') }}</p>
                <div>
                    <p class="loop-label">{{ __('loop.interests') }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ($sectors as $key => $label)
                            <label class="flex items-center gap-2 rounded-xl bg-chalk px-3 py-2 text-sm">
                                <input type="checkbox" name="interests[]" value="{{ $key }}" @checked(in_array($key, old('interests', $existing?->interests ?? []), true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                </div>
            </div>

            <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                <p class="text-sm text-ink-muted">{{ __('loop.member_pin_blurb') }}</p>
                <div>
                    <label class="loop-label">{{ __('loop.create_pin') }}</label>
                    <input name="pin" inputmode="numeric" maxlength="6" class="loop-input text-center text-xl tracking-[0.3em]" :required="step === 3" autocomplete="new-password">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.confirm_pin') }}</label>
                    <input name="pin_confirmation" inputmode="numeric" maxlength="6" class="loop-input text-center text-xl tracking-[0.3em]" :required="step === 3" autocomplete="new-password">
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                    <button type="submit" class="loop-btn-mint flex-1">{{ __('loop.continue') }}</button>
                </div>
            </div>
        </form>
    </div>
</x-guest-layout>
