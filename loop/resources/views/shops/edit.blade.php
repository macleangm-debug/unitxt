<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.edit_shop') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.code') }}: <span class="font-semibold text-ink">{{ $shop->code }}</span></p>
            </div>
            <x-settings-back />
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
                :country="$shop->business->country"
                :required="true"
            />
        </div>
        <div>
            <label class="loop-label">{{ __('loop.address') }}</label>
            <input name="address" value="{{ old('address', $shop->address) }}" class="loop-input">
        </div>
        <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
            <div>
                <label class="loop-label">{{ __('loop.country_prefix') }}</label>
                <select name="country_code" class="loop-input">
                    @foreach ($countries as $meta)
                        <option value="{{ $meta['dial'] }}" @selected(old('country_code', $dial) === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.phone') }}</label>
                <input name="phone" value="{{ old('phone', $localPhone) }}" class="loop-input" placeholder="+255 712 000 001">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shop->is_active))>
            {{ __('loop.shop_is_active') }}
        </label>
        <p class="rounded-2xl bg-chalk/80 px-4 py-3 text-xs text-ink-muted">{{ __('loop.shared_logo_hint') }} <a href="{{ route('business.edit') }}" class="font-semibold text-mint-deep">{{ __('loop.edit_business_logo') }}</a></p>
        <button class="loop-btn-mint w-full">{{ __('loop.save_changes') }}</button>
    </form>
</x-app-layout>
