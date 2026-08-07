<x-guest-layout>
    <div class="mx-auto max-w-md">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.affiliates') }}</p>
        <h1 class="mt-2 font-display text-3xl font-semibold">{{ __('loop.affiliate_login') }}</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ __('loop.affiliate_login_blurb') }}</p>

        <form method="POST" action="{{ route('affiliate.login') }}" class="mt-8 space-y-4" x-data="{ method: 'pin' }">
            @csrf
            <div>
                <label class="loop-label">{{ __('loop.country_code') }}</label>
                <select name="country_code" class="loop-input" required>
                    @foreach ($countries as $code => $meta)
                        <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial($preferredCountry)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.phone') }}</label>
                <input name="phone" value="{{ old('phone') }}" class="loop-input" required>
            </div>

            <div class="flex gap-2">
                <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="method==='pin' ? 'bg-ink text-white' : 'bg-white ring-1 ring-ink/10'" @click="method='pin'">{{ __('loop.use_pin') }}</button>
                <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="method==='password' ? 'bg-ink text-white' : 'bg-white ring-1 ring-ink/10'" @click="method='password'">{{ __('loop.use_password') }}</button>
            </div>
            <input type="hidden" name="method" :value="method">

            <div x-show="method==='pin'">
                <label class="loop-label">{{ __('loop.pin') }} ({{ $pinLength }} {{ __('loop.digits') }})</label>
                <input type="password" inputmode="numeric" name="pin" maxlength="{{ $pinLength }}" class="loop-input" autocomplete="one-time-code">
                <x-input-error :messages="$errors->get('pin')" class="mt-1" />
            </div>
            <div x-show="method==='password'" x-cloak>
                <label class="loop-label">{{ __('loop.password') }}</label>
                <input type="password" name="password" class="loop-input" autocomplete="current-password">
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <button class="loop-btn-mint w-full">{{ __('loop.log_in') }}</button>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
            <a href="{{ route('affiliate.activate') }}" class="font-semibold text-mint-deep">{{ __('loop.activate_account') }}</a>
            ·
            <a href="{{ route('affiliates.status') }}" class="font-semibold text-ink">{{ __('loop.check_status') }}</a>
        </p>
    </div>
</x-guest-layout>
