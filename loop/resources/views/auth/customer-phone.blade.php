<x-guest-layout>
    <form method="POST" action="{{ route('customer.send') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">Enter your phone</h1>
            <p class="mt-1 text-sm text-ink-muted">That’s your Loop ID. We’ll send a one-time code.</p>
        </div>
        <div>
            <label class="loop-label">Country</label>
            <select name="country_code" class="loop-input">
                @foreach ($countries as $code => $meta)
                    <option value="{{ $meta['dial'] }}" @selected(old('country_code', '+255') === $meta['dial'])>{{ $meta['flag'] }} {{ $meta['name'] }} {{ $meta['dial'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">Phone number</label>
            <input name="phone" value="{{ old('phone') }}" class="loop-input text-lg tracking-wide" placeholder="712 345 678" required autofocus>
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <button class="loop-btn-mint w-full">Continue</button>
        <p class="text-center text-sm text-ink-muted">Not on Loop yet? <a href="{{ route('discover') }}" class="underline">Browse campaigns</a> — a shop can add you on your next visit.</p>
    </form>
</x-guest-layout>
