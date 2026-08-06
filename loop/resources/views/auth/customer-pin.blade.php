<x-guest-layout>
    <form method="POST" action="{{ route('customer.pin.verify') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">{{ __('loop.enter_pin') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $auth['country_code'] }} {{ $auth['phone'] }}</p>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.pin') }}</label>
            <input name="pin" inputmode="numeric" maxlength="6" class="loop-input text-center text-2xl tracking-[0.4em]" required autofocus>
            <x-input-error :messages="$errors->get('pin')" class="mt-1" />
        </div>
        <button class="loop-btn-mint w-full">{{ __('loop.continue') }}</button>
    </form>
</x-guest-layout>
