<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">Front desk staff</h1>
        <p class="mt-1 text-ink-muted">They log in with phone + password and only use the till. You can disable access anytime.</p>
    </x-slot>

    <form method="POST" action="{{ route('staff.store') }}" class="loop-panel max-w-xl space-y-4 p-6 mb-8">
        @csrf
        <h2 class="font-display text-lg font-semibold">Add staff</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="loop-label">First name</label><input name="first_name" class="loop-input" required></div>
            <div><label class="loop-label">Last name</label><input name="last_name" class="loop-input" required></div>
        </div>
        <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
            <div>
                <label class="loop-label">Code</label>
                <select name="country_code" class="loop-input">
                    @foreach ($countries as $meta)
                        <option value="{{ $meta['dial'] }}">{{ $meta['dial'] }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="loop-label">Phone</label><input name="phone" class="loop-input" required></div>
        </div>
        <div><label class="loop-label">Temp password</label><input type="password" name="password" class="loop-input" required></div>
        <button class="loop-btn">Add front desk</button>
    </form>

    <div class="space-y-3">
        @forelse ($staff as $member)
            <div class="loop-panel flex flex-wrap items-center justify-between gap-3 p-4">
                <div>
                    <p class="font-semibold">{{ $member->name }}</p>
                    <p class="text-sm text-ink-muted">{{ $member->full_phone }}</p>
                </div>
                <form method="POST" action="{{ route('staff.toggle', $member) }}">
                    @csrf
                    @method('PATCH')
                    <button class="loop-btn-ghost !py-2">{{ $member->is_active ? 'Disable' : 'Enable' }}</button>
                </form>
            </div>
        @empty
            <p class="text-ink-muted">No front desk staff yet.</p>
        @endforelse
    </div>
</x-app-layout>
