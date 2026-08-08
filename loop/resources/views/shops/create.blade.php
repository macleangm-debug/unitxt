<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.add_shop') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.add_shop_blurb', ['country' => $business->country]) }}</p>
    </x-slot>

    <form method="POST" action="{{ route('shops.store') }}" class="loop-panel mx-auto max-w-xl space-y-5 p-6 sm:p-8">
        @csrf
        <div>
            <label class="loop-label">{{ __('loop.shop_name') }}</label>
            <input name="name" value="{{ old('name') }}" class="loop-input" required>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.city') }}</label>
            <input name="city" list="cities" value="{{ old('city', $business->city) }}" class="loop-input" required>
            <datalist id="cities">
                @foreach ($cities as $city)
                    <option value="{{ $city }}"></option>
                @endforeach
            </datalist>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.address') }}</label>
            <input name="address" value="{{ old('address') }}" class="loop-input" required>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.shop_address_required_help') }}</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
            <div>
                <label class="loop-label">{{ __('loop.country_prefix') }}</label>
                <select name="country_code" class="loop-input">
                    @foreach ($countries as $meta)
                        <option value="{{ $meta['dial'] }}" @selected(old('country_code', $defaultDial) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.phone') }}</label>
                <input name="phone" value="{{ old('phone') }}" class="loop-input" placeholder="+255 712 000 001">
            </div>
        </div>
        <p class="rounded-2xl bg-chalk/80 px-4 py-3 text-xs text-ink-muted">{{ __('loop.shared_logo_hint') }}</p>
        <button class="loop-btn-mint w-full">{{ __('loop.save_shop') }}</button>
    </form>
</x-app-layout>
