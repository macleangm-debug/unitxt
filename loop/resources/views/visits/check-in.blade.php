<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Check in</h1>
        <p class="mt-1 text-ink-muted">Enter the shop code to earn points from the active campaign.</p>
    </x-slot>

    <form method="POST" action="{{ route('visits.store') }}" class="loop-panel max-w-md space-y-4 p-6 animate-fade-up">
        @csrf
        <div>
            <label class="loop-label" for="code">Shop code</label>
            <input id="code" name="code" value="{{ old('code', $code) }}" class="loop-input uppercase tracking-wider" placeholder="SHOP-XXXXXX" required autofocus>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
            <x-input-error :messages="$errors->get('campaign')" class="mt-2" />
            <x-input-error :messages="$errors->get('visit')" class="mt-2" />
            <x-input-error :messages="$errors->get('shop')" class="mt-2" />
            <x-input-error :messages="$errors->get('customer')" class="mt-2" />
        </div>
        <button class="loop-btn-mint w-full">Earn points</button>
    </form>
</x-app-layout>
