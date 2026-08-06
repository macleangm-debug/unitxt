<x-guest-layout>
    <form method="POST" action="{{ route('business.register') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">Register your business</h1>
            <p class="mt-1 text-sm text-ink-muted">Tanzania-first. Takes about a minute.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">First name</label>
                <input name="first_name" value="{{ old('first_name') }}" class="loop-input" required>
                <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
            </div>
            <div>
                <label class="loop-label">Last name</label>
                <input name="last_name" value="{{ old('last_name') }}" class="loop-input" required>
            </div>
        </div>

        <div>
            <label class="loop-label">Country</label>
            <select name="country" class="loop-input">
                @foreach ($countries as $code => $meta)
                    <option value="{{ $code }}" @selected(old('country', 'TZ') === $code)>{{ $meta['flag'] }} {{ $meta['name'] }} ({{ $meta['dial'] }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="loop-label">Phone (login)</label>
            <input name="phone" value="{{ old('phone') }}" class="loop-input" placeholder="712 345 678" required>
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>

        <div>
            <label class="loop-label">Email (optional)</label>
            <input type="email" name="email" value="{{ old('email') }}" class="loop-input">
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">Password</label>
                <input type="password" name="password" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">Confirm</label>
                <input type="password" name="password_confirmation" class="loop-input" required>
            </div>
        </div>

        <hr class="border-ink/10">

        <div>
            <label class="loop-label">Business name</label>
            <input name="business_name" value="{{ old('business_name') }}" class="loop-input" required>
        </div>

        <div>
            <label class="loop-label">Sector</label>
            <select name="sector" class="loop-input" required>
                @foreach ($sectors as $key => $label)
                    <option value="{{ $key }}" @selected(old('sector') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="loop-label">City</label>
                <input name="city" value="{{ old('city', 'Dar es Salaam') }}" class="loop-input">
            </div>
            <div>
                <label class="loop-label">First shop name</label>
                <input name="shop_name" value="{{ old('shop_name') }}" class="loop-input" required>
            </div>
        </div>

        <button class="loop-btn w-full">Create Loop account</button>
        <p class="text-center text-sm text-ink-muted">Already registered? <a href="{{ route('staff.login') }}" class="underline">Staff login</a></p>
    </form>
</x-guest-layout>
