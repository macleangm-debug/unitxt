<x-guest-split-layout>
    <x-slot:aside>
        <div class="flex h-full flex-col justify-between p-8 text-white lg:p-12">
            <div>
                <a href="/" class="flex items-center gap-3">
                    <x-loop-logo class="h-10 w-10" />
                    <span class="font-display text-2xl font-semibold">Loop</span>
                </a>
                <p class="mt-10 font-display text-3xl font-semibold leading-tight lg:text-4xl">{{ __('loop.grow_with_phone') }}</p>
                <p class="mt-4 max-w-sm text-sm text-white/70">{{ __('loop.business_blurb') }}</p>
            </div>
            <div class="mt-10 space-y-3 text-sm text-white/65">
                <p>◆ {{ __('loop.flex_campaigns') }}</p>
                <p>◆ {{ __('loop.front_desk_ready') }}</p>
                <p>◆ {{ __('loop.one_phone') }}</p>
            </div>
        </div>
    </x-slot:aside>

    <div
        x-data="{
            step: {{ $errors->any() ? max(1, (int) old('step', 4)) : 1 }},
            country: @js(old('country', $preferredCountry)),
            dials: @js(collect(\App\Support\Countries::enabledOptions())->mapWithKeys(fn ($m, $c) => [$c => $m['dial']])->all()),
            password: '',
            passwordConfirm: '',
            get dial() { return this.dials[this.country] || '+255'; },
            get passwordsMatch() {
                return this.password !== '' && this.password === this.passwordConfirm;
            },
            goPasswordNext() {
                if (!this.passwordsMatch) return;
                this.step = 4;
            }
        }"
        class="loop-guest-card mx-auto w-full max-w-md"
        @sheet-selected.window="if ($event.detail.name === 'country') { country = $event.detail.value }"
    >
        <p class="mb-6 text-xs font-semibold text-ink-muted">{{ __('loop.step') }} <span x-text="step"></span>/4</p>

        <h1 class="font-display text-2xl font-semibold">{{ __('loop.cta_business') }}</h1>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.grow_with_phone') }}</p>

        <form method="POST" action="{{ route('business.register') }}" class="mt-6 space-y-4" autocomplete="off" data-loop-no-skeleton>
            @csrf
            <input type="hidden" name="step" :value="step">

            <div x-show="step === 1" class="space-y-4">
                <x-sheet-select
                    name="country"
                    :label="__('loop.country')"
                    :options="collect($countries)->mapWithKeys(fn ($meta, $code) => [$code => ($meta['flag'].' '.$meta['name'])])->all()"
                    :value="old('country', $preferredCountry)"
                    :required="true"
                    :placeholder="__('loop.country')"
                />
                <p class="text-xs text-ink-muted">{{ __('loop.country_first_hint') }}</p>
                <button type="button" @click="step = 2" class="loop-btn w-full">{{ __('loop.next') }}</button>
            </div>

            <div x-show="step === 2" x-cloak class="space-y-4">
                <div>
                    <label class="loop-label">{{ __('loop.first_name') }}</label>
                    <input name="first_name" value="{{ old('first_name') }}" class="loop-input" required>
                    <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.last_name') }}</label>
                    <input name="last_name" value="{{ old('last_name') }}" class="loop-input" required>
                    <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.phone') }}</label>
                    <x-phone-field
                        name="phone"
                        :dial="\App\Support\Countries::dial(old('country', $preferredCountry))"
                        x-dial="dial"
                        :value="old('phone')"
                        :required="true"
                    />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.email_optional') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="loop-input">
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="step = 1" class="loop-btn-ghost flex-1">{{ __('loop.back') }}</button>
                    <button type="button" @click="step = 3" class="loop-btn flex-1">{{ __('loop.next') }}</button>
                </div>
            </div>

            <div x-show="step === 3" x-cloak class="space-y-4">
                <div>
                    <label class="loop-label">{{ __('loop.password') }}</label>
                    <input type="password" name="password" class="loop-input" required x-model="password" autocomplete="off">
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" class="loop-input" required x-model="passwordConfirm" autocomplete="off"
                           :class="passwordConfirm && !passwordsMatch ? 'border-coral focus:border-coral focus:ring-coral' : ''">
                    <p x-show="passwordConfirm && !passwordsMatch" x-cloak class="mt-1 text-xs font-semibold text-coral">{{ __('loop.passwords_must_match') }}</p>
                    <p x-show="passwordsMatch" x-cloak class="mt-1 text-xs font-semibold text-mint-deep">{{ __('loop.passwords_match') }}</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="step = 2" class="loop-btn-ghost flex-1">{{ __('loop.back') }}</button>
                    <button type="button" class="loop-btn flex-1" @click="goPasswordNext()" :disabled="!passwordsMatch" :class="{ 'opacity-60': !passwordsMatch }">{{ __('loop.next') }}</button>
                </div>
            </div>

            <div x-show="step === 4" x-cloak class="space-y-4">
                <div>
                    <label class="loop-label">{{ __('loop.business_name') }}</label>
                    <input name="business_name" value="{{ old('business_name') }}" class="loop-input" required>
                    <x-input-error :messages="$errors->get('business_name')" class="mt-1" />
                </div>
                <x-sector-picker
                    name="sector"
                    :value="old('sector')"
                    :required="true"
                    :other-value="old('sector_other')"
                />
                <div>
                    <label class="loop-label">{{ __('loop.hotline') }}</label>
                    <x-phone-field
                        name="hotline"
                        :dial="\App\Support\Countries::dial(old('country', $preferredCountry))"
                        x-dial="dial"
                        hidden-dial-name="hotline_country_code"
                        :value="old('hotline')"
                    />
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.hotline_hint') }}</p>
                </div>
                <div
                    class="rounded-2xl border-2 border-violet bg-violet-soft/50 px-4 py-4"
                    x-data="{ open: {{ filled(old('referral_code', $referralCode ?? '')) ? 'true' : 'false' }} }"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 text-left"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                    >
                        <span>
                            <span class="block font-display text-base font-semibold text-ink">{{ __('loop.were_you_invited') }}</span>
                            <span class="mt-1 block text-xs text-ink-muted" x-show="!open">{{ __('loop.were_you_invited_body') }}</span>
                        </span>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet text-sm font-bold text-white" x-text="open ? '−' : '+'"></span>
                    </button>
                    <div class="mt-4" x-show="open" x-cloak>
                        <label class="loop-label">{{ __('loop.referral_code') }}</label>
                        <input name="referral_code" value="{{ old('referral_code', $referralCode ?? '') }}" class="loop-input uppercase" placeholder="ABCD1234" autocomplete="off">
                        @if (!empty($referrerBusiness))
                            <p class="mt-1 text-xs font-medium text-mint-deep">{{ __('loop.referred_by', ['name' => $referrerBusiness->name]) }}</p>
                        @elseif (!empty($referrerAffiliate))
                            <p class="mt-1 text-xs font-medium text-mint-deep">{{ __('loop.referred_by_affiliate') }}</p>
                        @else
                            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.referral_code_hint') }}</p>
                        @endif
                        <x-input-error :messages="$errors->get('referral_code')" class="mt-1" />
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="step = 3" class="loop-btn-ghost flex-1">{{ __('loop.back') }}</button>
                    <button class="loop-btn flex-1">{{ __('loop.create_account') }}</button>
                </div>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">{{ __('loop.already_registered') }} <a href="{{ route('staff.login') }}" class="underline">{{ __('loop.staff_login') }}</a></p>
    </div>
</x-guest-split-layout>
