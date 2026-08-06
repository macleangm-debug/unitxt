<x-guest-layout>
    <form method="POST" action="{{ route('staff.login') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">Staff login</h1>
            <p class="mt-1 text-sm text-ink-muted">Owners and front desk — phone + password.</p>
        </div>
        <div>
            <label class="loop-label">Country code</label>
            <select name="country_code" class="loop-input">
                @foreach ($countries as $code => $meta)
                    <option value="{{ $meta['dial'] }}" @selected(old('country_code', '+255') === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">Phone</label>
            <input name="phone" value="{{ old('phone') }}" class="loop-input" placeholder="712 345 678" required autofocus>
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <div>
            <label class="loop-label">Password</label>
            <input type="password" name="password" class="loop-input" required>
        </div>
        <label class="flex items-center gap-2 text-sm text-ink-muted">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <button class="loop-btn w-full">Log in</button>
        <p class="text-center text-sm text-ink-muted"><a href="{{ route('business.register') }}" class="underline">Register a business</a></p>
    </form>
</x-guest-layout>
