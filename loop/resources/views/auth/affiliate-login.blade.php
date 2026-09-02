<x-guest-layout
    :aside-stamp="__('loop.affiliate_stamp')"
    :aside-title="__('loop.affiliate_aside_title')"
    :aside-body="__('loop.affiliate_aside_body')"
    :aside-point1="__('loop.affiliate_aside_1')"
    :aside-point2="__('loop.affiliate_aside_2')"
    :aside-point3="__('loop.affiliate_aside_3')"
>
    <div class="mx-auto w-full max-w-md">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.affiliates') }}</p>
        <h1 class="mt-2 font-display text-3xl font-semibold">{{ __('loop.affiliate_login') }}</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ __('loop.affiliate_login_blurb') }}</p>

        <form method="POST" action="{{ route('affiliate.login') }}" class="mt-8 space-y-4" autocomplete="off" data-lpignore="true" data-1p-ignore="true">
            @csrf
            <div>
                <label class="loop-label">{{ __('loop.phone') }}</label>
                <div class="mt-1 flex overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-sm focus-within:border-violet focus-within:ring-1 focus-within:ring-violet">
                    <select name="country_code" class="shrink-0 border-0 border-r border-ink/10 bg-chalk py-3 pl-3 pr-8 text-sm font-semibold focus:ring-0" required>
                        @foreach ($countries as $code => $meta)
                            <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial($preferredCountry)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                        @endforeach
                    </select>
                    <input name="phone" value="{{ old('phone') }}" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base tracking-wide focus:ring-0" placeholder="7xxxxxxxx" required inputmode="numeric" pattern="[0-9]*" autocomplete="off" data-lpignore="true">
                </div>
            </div>

            <div>
                <label class="loop-label">{{ __('loop.pin') }} ({{ $pinLength }} {{ __('loop.digits') }})</label>
                <input type="text" inputmode="numeric" pattern="[0-9]*" name="pin" maxlength="{{ $pinLength }}" class="loop-input loop-secret" required autocomplete="off" data-lpignore="true" spellcheck="false">
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
