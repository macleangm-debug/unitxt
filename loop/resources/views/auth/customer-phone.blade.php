<x-guest-layout>
    <x-site-header class="!border-0" />
    <form method="POST" action="{{ route('customer.send') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">{{ __('loop.cta_customer') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Your phone is your Loop ID. Then use a PIN.') }}</p>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.country') }}</label>
            <select name="country_code" class="loop-input">
                @foreach ($countries as $code => $meta)
                    <option value="{{ $meta['dial'] }}" @selected(old('country_code', \App\Support\Countries::dial($preferredCountry)) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['name'] }} {{ $meta['dial'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.phone') }}</label>
            <input name="phone" value="{{ old('phone') }}" class="loop-input text-lg tracking-wide" placeholder="712 345 678" required autofocus>
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <button class="loop-btn-mint w-full">{{ __('loop.continue') }}</button>
        <p class="text-center text-sm text-ink-muted"><a href="{{ route('discover') }}" class="underline">{{ __('loop.browse_campaigns') }}</a></p>
    </form>
</x-guest-layout>
