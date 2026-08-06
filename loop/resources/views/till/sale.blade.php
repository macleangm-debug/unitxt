<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">
            @if ($customer)
                {{ $customer->name }}
            @else
                New Loop customer
            @endif
        </h1>
        <p class="mt-1 text-ink-muted">
            {{ $country_code }} {{ $phone }} · {{ $shop->name }}
            · {{ $channel === 'phone_order' ? 'Phone order' : 'In store' }}
        </p>
    </x-slot>

    @if ($customer && $membership)
        <div class="mb-6 grid gap-3 sm:grid-cols-3 animate-fade-up">
            <div class="loop-panel p-4">
                <p class="text-sm text-ink-muted">Balance</p>
                <p class="font-display text-3xl font-semibold">{{ $membership->points_balance }}</p>
            </div>
            <div class="loop-panel p-4 sm:col-span-2">
                <p class="text-sm text-ink-muted">Available rewards</p>
                <p class="mt-1 text-sm">
                    @forelse ($membership->availableRewards() as $reward)
                        <span class="mr-2 inline-block rounded-lg bg-mint-soft px-2 py-1 font-medium">{{ $reward->name }} ({{ $reward->points_cost }} pts)</span>
                    @empty
                        <span class="text-ink-muted">None unlocked yet</span>
                    @endforelse
                </p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('till.store') }}" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        <input type="hidden" name="shop_id" value="{{ $shop->id }}">
        <input type="hidden" name="country_code" value="{{ $country_code }}">
        <input type="hidden" name="phone" value="{{ $phone }}">
        <input type="hidden" name="channel" value="{{ $channel }}">

        @unless ($customer)
            <p class="rounded-xl bg-chalk px-3 py-2 text-sm text-ink-muted">Not on Loop yet — capture details once. Same phone won’t be duplicated later.</p>
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
                <label class="loop-label">Birth date (for birthday campaigns)</label>
                <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="loop-input">
            </div>
            <div>
                <label class="loop-label">Email (optional)</label>
                <input type="email" name="email" value="{{ old('email') }}" class="loop-input">
            </div>
        @endunless

        <div>
            <label class="loop-label">Amount spent ({{ $business->currency }})</label>
            <input type="number" step="0.01" min="0.01" name="amount_spent" value="{{ old('amount_spent') }}" class="loop-input text-xl" required>
            @if ($campaign)
                <p class="mt-1 text-xs text-ink-muted">{{ $campaign->ruleSummary($business->currency) }}</p>
            @endif
            <x-input-error :messages="$errors->get('amount_spent')" class="mt-1" />
        </div>

        <div>
            <label class="loop-label">Apply reward at till (optional)</label>
            <select name="reward_id" class="loop-input">
                <option value="">No reward this time</option>
                @foreach ($rewards as $reward)
                    <option value="{{ $reward->id }}" @selected(old('reward_id') == $reward->id)>
                        {{ $reward->name }} · {{ $reward->points_cost }} pts · {{ $reward->label() }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('reward_id')" class="mt-1" />
        </div>

        <div>
            <label class="loop-label">Receipt / bill ref (optional)</label>
            <input name="receipt_ref" value="{{ old('receipt_ref') }}" class="loop-input" placeholder="INV-204">
        </div>

        <button class="loop-btn-mint w-full">Complete sale & award points</button>
        <a href="{{ route('till.index') }}" class="block text-center text-sm text-ink-muted underline">Cancel</a>
    </form>
</x-app-layout>
