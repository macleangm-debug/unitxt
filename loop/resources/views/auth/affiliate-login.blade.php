<x-guest-layout>
    <div class="mx-auto w-full max-w-md">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.affiliates') }}</p>
        <h1 class="mt-2 font-display text-3xl font-semibold">{{ __('loop.affiliate_login') }}</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ __('loop.affiliate_login_blurb') }}</p>

        <form method="POST" action="{{ route('affiliate.login') }}" class="mt-8 space-y-4" autocomplete="off" data-lpignore="true" data-1p-ignore="true" x-data="{ method: 'pin' }">
            @csrf
            <div>
                <label class="loop-label">{{ __('loop.phone') }}</label>
                <div class="mt-1 flex overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-sm focus-within:border-violet focus-within:ring-1 focus-within:ring-violet">
                    <select name="country_code" class="shrink-0 border-0 border-r border-ink/10 bg-chalk py-3 pl-3 pr-8 text-sm font-semibold focus:ring-0" required>
                        @foreach ($countries as $code => $meta)
                            <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial($preferredCountry)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                        @endforeach
                    </select>
                    <input name="phone" value="{{ old('phone') }}" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base tracking-wide focus:ring-0" placeholder="7xxxxxxxx" required inputmode="tel" autocomplete="off" data-lpignore="true">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="method==='pin' ? 'bg-ink text-white' : 'bg-white ring-1 ring-ink/10'" @click="method='pin'">{{ __('loop.use_pin') }}</button>
                <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="method==='password' ? 'bg-ink text-white' : 'bg-white ring-1 ring-ink/10'" @click="method='password'">{{ __('loop.use_password') }}</button>
            </div>
            <input type="hidden" name="method" :value="method">

            <div x-show="method==='pin'">
                <label class="loop-label">{{ __('loop.pin') }} ({{ $pinLength }} {{ __('loop.digits') }})</label>
                <input type="password" inputmode="numeric" name="pin" maxlength="{{ $pinLength }}" class="loop-input" autocomplete="off" data-lpignore="true">
            </div>
            <div x-show="method==='password'" x-cloak>
                <label class="loop-label">{{ __('loop.password') }}</label>
                <input type="password" name="password" class="loop-input" autocomplete="off" data-lpignore="true">
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
