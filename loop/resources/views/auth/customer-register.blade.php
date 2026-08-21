@php
    $needsPin = $needsPin ?? true;
    $knownName = $knownName ?? false;
    $knownBirthday = $knownBirthday ?? false;
    $knownGender = $knownGender ?? false;
    $sectors = $sectors ?? [];
    $registerSteps = array_values(array_filter([
        1 => __('loop.member_intro'),
        2 => __('loop.interests'),
        3 => $needsPin ? __('loop.create_pin') : null,
    ]));
    $registerSteps = array_combine(range(1, count($registerSteps)), $registerSteps);
    $totalSteps = count($registerSteps);
    $errorStep = 1;
    if ($errors->hasAny(['interests'])) {
        $errorStep = 2;
    } elseif ($errors->hasAny(['pin', 'pin_confirmation'])) {
        $errorStep = $needsPin ? 3 : 2;
    }
    $initialStep = $errors->any() ? $errorStep : (int) old('_step', 1);
    $knownCity = filled($existing?->city);
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
        x-data="memberRegisterWizard({ step: {{ (int) $initialStep }}, total: {{ (int) $totalSteps }}, force: {{ $errors->any() ? 'true' : 'false' }} })"
        x-effect="persist()"
    >
        <div class="mb-4">
            <h1 class="font-display text-2xl font-semibold">
                @if ($knownName)
                    {{ __('loop.member_hi', ['name' => $existing->first_name]) }}
                @else
                    {{ $existing ? __('loop.finish_your_loop') : __('loop.create_your_loop') }}
                @endif
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
                @if ($knownName)
                    <p class="text-sm text-ink-muted">{{ __('loop.member_details_ready') }}</p>
                    <div class="rounded-2xl bg-chalk px-4 py-3 text-sm">
                        <p class="font-semibold">{{ $existing->name }}</p>
                        @if ($knownBirthday)
                            <p class="mt-1 text-ink-muted">{{ __('loop.birthday') }} · {{ $existing->birth_day }}/{{ $existing->birth_month }}</p>
                        @endif
                        @if ($knownGender)
                            <p class="mt-1 text-ink-muted">{{ $existing->gender === 'female' ? __('loop.gender_female') : __('loop.gender_male') }}</p>
                        @endif
                    </div>
                    <input type="hidden" name="first_name" value="{{ old('first_name', $existing->first_name) }}">
                    @if (filled($existing->last_name))
                        <input type="hidden" name="last_name" value="{{ old('last_name', $existing->last_name) }}">
                    @else
                        <div>
                            <label class="loop-label">{{ __('loop.last_name') }} ({{ __('loop.optional') }})</label>
                            <input name="last_name" value="{{ old('last_name', $existing->last_name) }}" class="loop-input" autocomplete="family-name">
                        </div>
                    @endif
                @else
                    <p class="text-sm text-ink-muted">{{ __('loop.member_intro_blurb') }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.first_name') }}</label>
                        <input name="first_name" value="{{ old('first_name', $existing?->first_name) }}" class="loop-input" :required="step === 1" autocomplete="given-name">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.last_name') }} ({{ __('loop.optional') }})</label>
                        <input name="last_name" value="{{ old('last_name', $existing?->last_name) }}" class="loop-input" autocomplete="family-name">
                    </div>
                @endif

                @if ($knownCity)
                    <input type="hidden" name="city" value="{{ old('city', $existing->city) }}">
                @else
                    <div>
                        <x-city-sheet-select
                            name="city"
                            :label="__('loop.city')"
                            :value="old('city', $existing?->city ?? ($cities[0] ?? ''))"
                            :cities="$cities"
                            :country="old('country', $country)"
                            :required="true"
                        />
                    </div>
                @endif

                <input type="hidden" name="country" value="{{ old('country', $country) }}">

                @if ($knownBirthday)
                    <input type="hidden" name="birth_month" value="{{ old('birth_month', $existing->birth_month) }}">
                    <input type="hidden" name="birth_day" value="{{ old('birth_day', $existing->birth_day) }}">
                @else
                    <div>
                        <p class="loop-label">{{ __('loop.birthday') }}</p>
                        <div class="mt-1">
                            <x-birthday-fields :month="old('birth_month', $existing?->birth_month)" :day="old('birth_day', $existing?->birth_day)" />
                        </div>
                    </div>
                @endif

                @if ($knownGender)
                    <input type="hidden" name="gender" value="{{ old('gender', $existing->gender) }}">
                @else
                    <x-gender-field :value="old('gender', $existing?->gender)" />
                @endif

                <button type="button" class="loop-btn-mint w-full" @click="next()">{{ __('loop.continue') }}</button>
            </div>

            <div data-step="2" x-show="step === 2" x-cloak class="space-y-4">
                <p class="font-display text-lg font-semibold">{{ __('loop.what_are_you_interested_in') }}</p>
                <p class="text-sm text-ink-muted">{{ __('loop.member_interests_blurb') }}</p>
                <div x-data="{ q: '', all: false, showChip(search, featured) { const q = this.q.trim().toLowerCase(); if (q) return search.includes(q); return this.all || featured; } }">
                    <input type="search" x-model="q" class="loop-input" placeholder="{{ __('loop.search_business_type') }}" autocomplete="off">
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (\App\Support\Sectors::pickerRecords() as $row)
                            @continue($row['key'] === 'other')
                            <label
                                class="cursor-pointer rounded-full border border-ink/10 bg-chalk px-3 py-2 text-sm font-semibold has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft"
                                x-show="showChip(@js($row['search']), @js((bool) $row['featured']))"
                                @if (! $row['featured']) x-cloak @endif
                            >
                                <input type="checkbox" name="interests[]" value="{{ $row['key'] }}" class="sr-only" @checked(in_array($row['key'], old('interests', $existing?->interests ?? []), true))>
                                {{ $row['label'] }}
                            </label>
                        @endforeach
                    </div>
                    <button type="button" class="mt-3 text-sm font-semibold text-violet" x-show="!all && !q.trim()" x-cloak @click="all = true">{{ __('loop.see_all_sectors') }}</button>
                </div>
                <div class="flex gap-3">
                    <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                    @if ($needsPin)
                        <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                    @else
                        <button type="submit" class="loop-btn-mint flex-1">{{ __('loop.youre_on_loop') }}</button>
                    @endif
                </div>
            </div>

            @if ($needsPin)
                <div data-step="3" x-show="step === 3" x-cloak class="space-y-4">
                    <p class="text-sm text-ink-muted">{{ __('loop.member_pin_blurb') }}</p>
                    <div>
                        <label class="loop-label">{{ __('loop.create_pin') }}</label>
                        <input name="pin" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="loop-input loop-secret text-center text-xl tracking-[0.3em]" :required="step === 3" autocomplete="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true">
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.confirm_pin') }}</label>
                        <input name="pin_confirmation" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="loop-input loop-secret text-center text-xl tracking-[0.3em]" :required="step === 3" autocomplete="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                        <button type="submit" class="loop-btn-mint flex-1">{{ __('loop.youre_on_loop') }}</button>
                    </div>
                </div>
            @endif
        </form>
    </div>
</x-guest-layout>
