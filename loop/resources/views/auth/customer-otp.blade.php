<x-guest-layout>
    <form method="POST" action="{{ route('customer.verify') }}" class="space-y-4">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">Enter code</h1>
            <p class="mt-1 text-sm text-ink-muted">Sent to {{ $auth['country_code'] }} {{ $auth['phone'] }}</p>
        </div>
        @if (session('status'))
            <div class="rounded-xl bg-mint-soft px-3 py-2 text-sm">{{ session('status') }}</div>
        @endif
        <div>
            <label class="loop-label">6-digit code</label>
            <input name="code" class="loop-input text-center text-2xl tracking-[0.4em]" maxlength="6" required autofocus>
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
        </div>
        <button class="loop-btn-mint w-full">Open my Loop</button>
        <p class="text-center text-sm"><a href="{{ route('customer.login') }}" class="underline text-ink-muted">Use a different number</a></p>
    </form>
</x-guest-layout>
