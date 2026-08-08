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

    <div x-data="{ step: {{ $errors->any() ? 1 : 1 }} }" class="mx-auto w-full max-w-md">
        <p class="mb-6 text-xs font-semibold text-ink-muted">{{ __('loop.step') }} <span x-text="step"></span>/4</p>

        <h1 class="font-display text-2xl font-semibold">{{ __('loop.cta_business') }}</h1>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.grow_with_phone') }}</p>

        <form method="POST" action="{{ route('business.register') }}" class="mt-6 space-y-4">
            @csrf

            <div x-show="step === 1" class="space-y-4">
                <div>
                    <label class="loop-label">{{ __('loop.country') }}</label>
                    <select name="country" class="loop-input" required>
                        @foreach ($countries as $code => $meta)
                            <option value="{{ $code }}" @selected(old('country', $preferredCountry) === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.country_first_hint') }}</p>
                </div>
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
                    <input name="phone" value="{{ old('phone') }}" class="loop-input" placeholder="712 345 678" required>
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
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
                    <input type="password" name="password" class="loop-input" required>
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" class="loop-input" required>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="step = 2" class="loop-btn-ghost flex-1">{{ __('loop.back') }}</button>
                    <button type="button" @click="step = 4" class="loop-btn flex-1">{{ __('loop.next') }}</button>
                </div>
            </div>

            <div x-show="step === 4" x-cloak class="space-y-4">
                <div>
                    <label class="loop-label">{{ __('loop.business_name') }}</label>
                    <input name="business_name" value="{{ old('business_name') }}" class="loop-input" required>
                    <x-input-error :messages="$errors->get('business_name')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.sector') }}</label>
                    <select name="sector" id="sector" class="loop-input" onchange="document.getElementById('other-sector-box').classList.toggle('hidden', this.value !== 'other')">
                        @foreach ($sectors as $key => $label)
                            <option value="{{ $key }}" @selected(old('sector', 'coffee') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="other-sector-box" class="{{ old('sector') === 'other' ? '' : 'hidden' }}">
                    <label class="loop-label">{{ __('loop.other_sector') }}</label>
                    <input name="sector_other" value="{{ old('sector_other') }}" class="loop-input">
                    <x-input-error :messages="$errors->get('sector_other')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.hotline') }}</label>
                    <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
                        <select name="hotline_country_code" class="loop-input">
                            @foreach ($countries as $code => $meta)
                                <option value="{{ $meta['dial'] }}" @selected(old('hotline_country_code', \App\Support\Countries::dial(old('country', $preferredCountry))) === $meta['dial'])>{{ $meta['dial'] }}</option>
                            @endforeach
                        </select>
                        <input name="hotline" value="{{ old('hotline') }}" class="loop-input" placeholder="712 345 678">
                    </div>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.hotline_hint') }}</p>
                </div>
                <div class="rounded-2xl bg-chalk px-4 py-3" x-data="{ hasCode: {{ old('referral_code', $referralCode ?? '') ? 'true' : 'false' }} }">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input type="checkbox" class="rounded border-ink/20 text-mint-deep focus:ring-mint" x-model="hasCode"
                               @checked(old('referral_code', $referralCode ?? '') !== null && old('referral_code', $referralCode ?? '') !== '')>
                        <span class="text-sm font-semibold">{{ __('loop.i_have_referral_code') }}</span>
                    </label>
                    <div class="mt-3" x-show="hasCode" x-cloak>
                        <label class="loop-label">{{ __('loop.referral_code') }}</label>
                        <input name="referral_code" value="{{ old('referral_code', $referralCode ?? '') }}" class="loop-input uppercase" placeholder="ABCD1234">
                        @if (!empty($referrerBusiness))
                            <p class="mt-1 text-xs font-medium text-mint-deep">{{ __('loop.referred_by', ['name' => $referrerBusiness->name]) }}</p>
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
