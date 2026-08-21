@php
    $dials = collect($countries)->mapWithKeys(fn ($m, $c) => [$c => $m['dial']])->all();
    $initialStep = (int) old('_step', 1);
    if ($errors->hasAny(['first_name', 'last_name', 'country', 'phone', 'email'])) {
        $initialStep = 1;
    } elseif ($errors->hasAny(['city', 'district', 'address'])) {
        $initialStep = 2;
    } elseif ($errors->hasAny(['id_type', 'id_number'])) {
        $initialStep = 3;
    }
    $initialStep = max(1, min(3, $initialStep));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="loop-no-skeleton">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Loop — {{ __('loop.become_affiliate') }}</title>
    @include('partials.head-boot')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|sora:500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink">
<x-site-header>
    <x-slot:actions>
        <a href="{{ route('affiliate.login') }}" class="loop-btn !py-2 text-sm">{{ __('loop.log_in') }}</a>
    </x-slot:actions>
</x-site-header>

<main class="loop-shell py-10">
    <div class="mx-auto max-w-xl">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.affiliates') }}</p>
        <h1 class="mt-2 font-display text-3xl font-semibold">{{ __('loop.affiliate_apply_title') }}</h1>
        <p class="mt-2 text-ink-muted">{{ __('loop.affiliate_apply_body') }}</p>

        <div
            x-data="affiliateApplyWizard({
                step: {{ $initialStep }},
                country: @js(old('country', $preferredCountry)),
                dials: @js($dials),
            })"
            class="mt-8"
            data-loop-no-skeleton
            @sheet-selected="if ($event.detail.name === 'country') country = $event.detail.value"
        >
            <div class="mb-6 flex items-center gap-2">
                @foreach ([1 => 'affiliate_step_person', 2 => 'affiliate_step_residence', 3 => 'affiliate_step_identity'] as $n => $label)
                    <button type="button" @click="go({{ $n }})" class="flex-1 rounded-2xl px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-[0.08em] transition sm:text-xs"
                        :class="step === {{ $n }} ? 'bg-ink text-white' : (step > {{ $n }} ? 'bg-mint/25 text-ink' : 'bg-chalk text-ink-muted')">
                        {{ $n }}. {{ __('loop.'.$label) }}
                    </button>
                @endforeach
            </div>

            <form
                x-ref="form"
                method="POST"
                action="{{ route('affiliates.apply.store') }}"
                class="space-y-5 rounded-[2rem] border border-ink/10 bg-white/90 p-6 shadow-[0_24px_70px_rgba(11,31,42,0.08)] sm:p-8"
                data-loop-no-skeleton
            >
                @csrf
                <input type="hidden" name="_step" :value="step">

                <div data-step="1" x-show="step === 1">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.affiliate_step_person') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.affiliate_step_person_help') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.first_name') }}</label>
                            <input name="first_name" value="{{ old('first_name') }}" class="loop-input" :required="step === 1" autocomplete="given-name">
                            <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.last_name') }}</label>
                            <input name="last_name" value="{{ old('last_name') }}" class="loop-input" :required="step === 1" autocomplete="family-name">
                            <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <x-sheet-select
                            name="country"
                            :label="__('loop.country')"
                            :options="collect($countries)->mapWithKeys(fn ($meta, $code) => [$code => ($meta['flag'].' '.$meta['name'])])->all()"
                            :value="old('country', $preferredCountry)"
                            :required="true"
                            :search-placeholder="__('loop.search')"
                        />
                    </div>
                    <div class="mt-4">
                        <label class="loop-label">{{ __('loop.phone') }}</label>
                        <div class="mt-1 flex overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-sm focus-within:border-violet focus-within:ring-1 focus-within:ring-violet">
                            <span class="flex shrink-0 items-center border-r border-ink/10 bg-chalk px-3.5 text-sm font-semibold tabular-nums text-ink" x-text="dials[country] || '+255'">{{ \App\Support\Countries::dial(old('country', $preferredCountry)) }}</span>
                            <input name="phone" value="{{ old('phone') }}" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base tracking-wide focus:ring-0" :required="step === 1" placeholder="7xxxxxxxx" inputmode="numeric" pattern="[0-9]*" autocomplete="tel">
                        </div>
                        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                    </div>
                    <div class="mt-4">
                        <label class="loop-label">{{ __('loop.email_optional') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="loop-input" placeholder="name@email.com" autocomplete="email">
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <button type="button" class="loop-btn-mint mt-6 w-full" @click="next()">{{ __('loop.continue') }}</button>
                </div>

                <div data-step="2" x-bind:hidden="step !== 2" @if ($initialStep !== 2) hidden @endif>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.affiliate_step_residence') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.affiliate_step_residence_help') }}</p>
                    <div class="mt-4">
                        <x-city-sheet-select name="city" :label="__('loop.city')" :value="old('city')" :required="true" country-field="country" />
                        <x-input-error :messages="$errors->get('city')" class="mt-1" />
                    </div>
                    <div class="mt-4">
                        <x-district-sheet-select name="district" :label="__('loop.district')" :value="old('district')" :required="true" city-field="city" />
                        <x-input-error :messages="$errors->get('district')" class="mt-1" />
                    </div>
                    <div class="mt-4">
                        <label class="loop-label">{{ __('loop.street') }}</label>
                        <input name="address" value="{{ old('address') }}" class="loop-input" :required="step === 2" placeholder="{{ __('loop.street_placeholder') }}">
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="go(1)">{{ __('loop.back') }}</button>
                        <button type="button" class="loop-btn-mint flex-1" @click="next()">{{ __('loop.continue') }}</button>
                    </div>
                </div>

                <div data-step="3" x-bind:hidden="step !== 3" @if ($initialStep !== 3) hidden @endif>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.affiliate_step_identity') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.identification_help') }}</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="loop-label">{{ __('loop.id_type') }}</label>
                            <select name="id_type" class="loop-input" :required="step === 3">
                                @foreach ($idTypes as $value => $label)
                                    <option value="{{ $value }}" @selected(old('id_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('id_type')" class="mt-1" />
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.id_number') }}</label>
                            <input name="id_number" value="{{ old('id_number') }}" class="loop-input" :required="step === 3">
                            <x-input-error :messages="$errors->get('id_number')" class="mt-1" />
                        </div>
                    </div>
                    <p class="mt-5 text-xs text-ink-muted">{{ __('loop.affiliate_review_note') }}</p>
                    <p class="mt-2 text-xs text-ink-muted">{{ __('loop.affiliate_payout_later_note') }}</p>
                    <div class="mt-6 flex gap-3">
                        <button type="button" class="loop-btn-ghost flex-1" @click="go(2)">{{ __('loop.back') }}</button>
                        <button class="loop-btn-mint flex-1">{{ __('loop.submit_application') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
<x-site-footer />
@include('partials.confirm-modal')
</body>
</html>
