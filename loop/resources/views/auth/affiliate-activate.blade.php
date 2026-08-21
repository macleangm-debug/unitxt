<x-guest-layout
    :aside-stamp="__('loop.affiliate_stamp')"
    :aside-title="__('loop.affiliate_aside_title')"
    :aside-body="__('loop.affiliate_aside_body')"
    :aside-point1="__('loop.affiliate_aside_1')"
    :aside-point2="__('loop.affiliate_aside_2')"
    :aside-point3="__('loop.affiliate_aside_3')"
>
    <div class="mx-auto max-w-md">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.affiliates') }}</p>
        <h1 class="mt-2 font-display text-3xl font-semibold">{{ __('loop.activate_account') }}</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ __('loop.affiliate_activate_blurb') }}</p>

        @if (! $affiliate)
            <form method="POST" action="{{ route('affiliate.activate.lookup') }}" class="mt-8 space-y-4">
                @csrf
                <div>
                    <label class="loop-label">{{ __('loop.country_code') }}</label>
                    <select name="country_code" class="loop-input" required>
                        @foreach ($countries as $code => $meta)
                            <option value="{{ $meta['dial'] }}" @selected(old('country_code', $prefillCountry ?? \App\Support\Countries::dial($preferredCountry)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.phone') }}</label>
                    <input name="phone" value="{{ old('phone', $prefillPhone) }}" class="loop-input" required inputmode="numeric" pattern="[0-9]*" autocomplete="off">
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <button class="loop-btn-mint w-full">{{ __('loop.continue') }}</button>
            </form>
        @else
            <div class="mt-6 rounded-2xl bg-mint-soft/60 px-4 py-3 text-sm">
                <p class="font-semibold">{{ $affiliate->name }}</p>
                <p class="text-ink-muted">{{ $affiliate->full_phone }} · {{ $affiliate->tracking_code }}</p>
            </div>
            <form method="POST" action="{{ route('affiliate.activate.store') }}" class="mt-6 space-y-4" autocomplete="off">
                @csrf
                <input type="hidden" name="country_code" value="{{ $affiliate->country_code }}">
                <input type="hidden" name="phone" value="{{ $affiliate->phone }}">
                <div>
                    <label class="loop-label">{{ __('loop.pin') }} ({{ $pinLength }} {{ __('loop.digits') }})</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="pin" maxlength="{{ $pinLength }}" class="loop-input loop-secret" required autocomplete="off" spellcheck="false">
                    <x-input-error :messages="$errors->get('pin')" class="mt-1" />
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.confirm_pin') }}</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" name="pin_confirmation" maxlength="{{ $pinLength }}" class="loop-input loop-secret" required autocomplete="off" spellcheck="false">
                </div>
                <button class="loop-btn-mint w-full">{{ __('loop.activate_account') }}</button>
            </form>
        @endif
    </div>
</x-guest-layout>
