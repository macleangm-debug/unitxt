<x-guest-layout
    :aside-title="__('loop.customer_aside_title')"
    :aside-body="__('loop.customer_aside_body')"
    :aside-stamp="__('loop.customer_stamp')"
    :aside-point1="__('loop.customer_aside_1')"
    :aside-point2="__('loop.customer_aside_2')"
    :aside-point3="__('loop.customer_aside_3')"
>
    <form method="POST" action="{{ route('customer.send') }}" class="space-y-4" autocomplete="off">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">{{ __('loop.cta_customer') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.customer_login_blurb') }}</p>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.phone') }}</label>
            <div class="mt-1 flex overflow-hidden rounded-2xl border border-ink/10 bg-white shadow-sm focus-within:border-violet focus-within:ring-1 focus-within:ring-violet">
                <select name="country_code" class="shrink-0 border-0 border-r border-ink/10 bg-chalk py-3 pl-3 pr-8 text-sm font-semibold focus:ring-0">
                    @foreach ($countries as $code => $meta)
                        <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial($preferredCountry)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                    @endforeach
                </select>
                <input name="phone" value="{{ old('phone') }}" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base tracking-wide focus:ring-0" placeholder="7xxxxxxxx" required autofocus inputmode="tel" autocomplete="off">
            </div>
        </div>
        <button class="loop-btn w-full">{{ __('loop.continue') }}</button>
        <p class="text-center text-sm text-ink-muted"><a href="{{ route('discover') }}" class="underline">{{ __('loop.browse_campaigns') }}</a></p>
    </form>
</x-guest-layout>
