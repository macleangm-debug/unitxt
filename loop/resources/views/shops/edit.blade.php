<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('shops.show', $shop)" />
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.edit_shop') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.code') }}: <span class="font-semibold text-ink">{{ $shop->code }}</span></p>
            </div>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('shops.update', $shop) }}" class="loop-panel mx-auto max-w-xl space-y-5 p-6 sm:p-8">
        @csrf
        @method('PUT')
        <div>
            <label class="loop-label">{{ __('loop.shop_name') }}</label>
            <input name="name" value="{{ old('name', $shop->name) }}" class="loop-input" required>
        </div>
        <div>
            <x-city-sheet-select
                name="city"
                :label="__('loop.city')"
                :value="old('city', $shop->city)"
                :cities="$cities"
                :country="$shop->business->country ?? 'TZ'"
                :required="true"
            />
        </div>
        <div>
            <label class="loop-label">{{ __('loop.address') }}</label>
            <input name="address" value="{{ old('address', $shop->address) }}" class="loop-input">
        </div>
        <div>
            <label class="loop-label">{{ __('loop.phone') }}</label>
            <x-phone-field
                name="phone"
                :dial="$dial"
                hidden-dial-name="country_code"
                :value="old('phone', $localPhone)"
            />
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shop->is_active))>
            {{ __('loop.shop_is_active') }}
        </label>
        <p class="rounded-2xl bg-chalk/80 px-4 py-3 text-xs text-ink-muted">{{ __('loop.shared_logo_hint') }} <a href="{{ route('business.edit') }}" class="font-semibold text-mint-deep">{{ __('loop.edit_business_logo') }}</a></p>
        <button class="loop-btn-mint w-full">{{ __('loop.save_changes') }}</button>
    </form>
</x-app-layout>
