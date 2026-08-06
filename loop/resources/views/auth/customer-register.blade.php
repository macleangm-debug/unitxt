<x-guest-layout>
    <form method="POST" action="{{ route('customer.register.store') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">Complete your Loop</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $auth['country_code'] }} {{ $auth['phone'] }} · tell us where you are and what you love.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">First name</label>
                <input name="first_name" value="{{ old('first_name') }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">Last name</label>
                <input name="last_name" value="{{ old('last_name') }}" class="loop-input" required>
            </div>
        </div>

        <div>
            <label class="loop-label">{{ __('loop.country') }}</label>
            <select name="country" id="country" class="loop-input" required>
                @foreach ($countries as $code => $meta)
                    <option value="{{ $code }}" @selected(old('country', $country) === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="loop-label">City</label>
            <input name="city" list="city-list" value="{{ old('city', $cities[0] ?? '') }}" class="loop-input" required>
            <datalist id="city-list">
                @foreach ($cities as $cityOption)
                    <option value="{{ $cityOption }}"></option>
                @endforeach
            </datalist>
        </div>

        <div>
            <label class="loop-label">Birth date (optional)</label>
            <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="loop-input">
        </div>

        <div>
            <label class="loop-label">Email (optional)</label>
            <input type="email" name="email" value="{{ old('email') }}" class="loop-input">
        </div>

        <div>
            <p class="loop-label">Interests</p>
            <p class="mt-1 text-xs text-ink-muted">We’ll surface matching shops and discounts first.</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach ($sectors as $key => $label)
                    <label class="flex items-center gap-2 rounded-xl bg-chalk px-3 py-2 text-sm">
                        <input type="checkbox" name="interests[]" value="{{ $key }}" @checked(in_array($key, old('interests', []), true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <button class="loop-btn-mint w-full">See shops near me</button>
    </form>
</x-guest-layout>
