<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('shops.index')" />
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.add_shop') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.add_shop_blurb', ['country' => $business->country]) }}</p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('shops.store') }}" class="loop-panel mx-auto max-w-xl space-y-5 p-6 sm:p-8">
        @csrf
        <div>
            <label class="loop-label">{{ __('loop.shop_name') }}</label>
            <input name="name" value="{{ old('name') }}" class="loop-input" required>
        </div>
        <div>
            <x-city-sheet-select
                name="city"
                :label="__('loop.city')"
                :value="old('city', $business->city)"
                :cities="$cities"
                :country="$business->country ?? 'TZ'"
                :required="true"
            />
        </div>
        <div>
            <label class="loop-label">{{ __('loop.address') }}</label>
            <input name="address" value="{{ old('address') }}" class="loop-input" required>
            <p class="mt-1 text-xs text-ink-muted">{{ __('loop.shop_address_required_help') }}</p>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.phone') }}</label>
            <x-phone-field
                name="phone"
                :dial="$defaultDial"
                hidden-dial-name="country_code"
                :value="old('phone')"
            />
        </div>
        <p class="rounded-2xl bg-chalk/80 px-4 py-3 text-xs text-ink-muted">{{ __('loop.shared_logo_hint') }}</p>
        <button class="loop-btn-mint w-full">{{ __('loop.save_shop') }}</button>
    </form>
</x-app-layout>
