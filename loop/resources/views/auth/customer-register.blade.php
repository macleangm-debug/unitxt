<x-guest-layout>
    <form method="POST" action="{{ route('customer.register.store') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">
                {{ $existing ? __('Finish your Loop') : __('Create your Loop') }}
            </h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $auth['country_code'] }} {{ $auth['phone'] }}</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">{{ __('loop.first_name') }}</label>
                <input name="first_name" value="{{ old('first_name', $existing?->first_name) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.last_name') }}</label>
                <input name="last_name" value="{{ old('last_name', $existing?->last_name) }}" class="loop-input" required>
            </div>
        </div>

        <div>
            <label class="loop-label">{{ __('loop.country') }}</label>
            <select name="country" class="loop-input" required>
                @foreach ($countries as $code => $meta)
                    <option value="{{ $code }}" @selected(old('country', $country) === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="loop-label">{{ __('loop.city') }}</label>
            <input name="city" list="city-list" value="{{ old('city', $existing?->city ?? ($cities[0] ?? '')) }}" class="loop-input" required>
            <datalist id="city-list">
                @foreach ($cities as $cityOption)
                    <option value="{{ $cityOption }}"></option>
                @endforeach
            </datalist>
        </div>

        <div>
            <p class="loop-label">{{ __('loop.birthday') }}</p>
            <div class="mt-1">
                <x-birthday-fields :month="old('birth_month', $existing?->birth_month)" :day="old('birth_day', $existing?->birth_day)" />
            </div>
        </div>

        <x-gender-field :value="old('gender', $existing?->gender)" />

        <div>
            <p class="loop-label">{{ __('loop.interests') }}</p>
            <div class="mt-2 grid grid-cols-2 gap-2">
                @foreach ($sectors as $key => $label)
                    <label class="flex items-center gap-2 rounded-xl bg-chalk px-3 py-2 text-sm">
                        <input type="checkbox" name="interests[]" value="{{ $key }}" @checked(in_array($key, old('interests', $existing?->interests ?? []), true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="loop-label">{{ __('loop.create_pin') }}</label>
            <input name="pin" inputmode="numeric" maxlength="6" class="loop-input text-center text-xl tracking-[0.3em]" required>
        </div>
        <div>
            <label class="loop-label">{{ __('Confirm PIN') }}</label>
            <input name="pin_confirmation" inputmode="numeric" maxlength="6" class="loop-input text-center text-xl tracking-[0.3em]" required>
        </div>

        <button class="loop-btn-mint w-full">{{ __('loop.continue') }}</button>
    </form>
</x-guest-layout>
