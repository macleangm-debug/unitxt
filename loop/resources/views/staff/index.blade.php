<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.staff') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('Front desk uses phone + password on Sale. Disable anytime.') }}</p>
    </x-slot>

    <div class="grid gap-3 sm:hidden">
        @foreach ($staff as $member)
            <div class="loop-panel p-4">
                <p class="font-semibold">{{ $member->name }}</p>
                <p class="text-sm text-ink-muted">{{ $member->full_phone }}</p>
                <form method="POST" action="{{ route('staff.toggle', $member) }}" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <button class="loop-btn-ghost !py-2 w-full">{{ $member->is_active ? __('Disable') : __('Enable') }}</button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mb-8 hidden overflow-hidden rounded-2xl border border-ink/10 bg-white sm:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-chalk text-ink-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('loop.phone') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staff as $member)
                    <tr class="border-t border-ink/5">
                        <td class="px-4 py-3 font-semibold">{{ $member->name }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $member->full_phone }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-lg px-2 py-1 text-xs font-semibold {{ $member->is_active ? 'bg-mint-soft' : 'bg-chalk text-ink-muted' }}">
                                {{ $member->is_active ? __('Active') : __('Disabled') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('staff.toggle', $member) }}">
                                @csrf
                                @method('PATCH')
                                <button class="text-sm font-semibold text-ink-muted hover:text-ink">{{ $member->is_active ? __('Disable') : __('Enable') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-ink-muted">{{ __('No front desk staff yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('staff.store') }}" class="loop-panel max-w-xl space-y-4 p-6">
        @csrf
        <h2 class="font-display text-lg font-semibold">{{ __('Add staff') }}</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="loop-label">{{ __('loop.first_name') }}</label><input name="first_name" class="loop-input" required></div>
            <div><label class="loop-label">{{ __('loop.last_name') }}</label><input name="last_name" class="loop-input" required></div>
        </div>
        <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
            <div>
                <label class="loop-label">{{ __('Code') }}</label>
                <select name="country_code" class="loop-input">
                    @foreach ($countries as $meta)
                        <option value="{{ $meta['dial'] }}">{{ $meta['dial'] }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="loop-label">{{ __('loop.phone') }}</label><input name="phone" class="loop-input" required></div>
        </div>
        <div><label class="loop-label">{{ __('Temp password') }}</label><input type="password" name="password" class="loop-input" required></div>
        <button class="loop-btn">{{ __('Add front desk') }}</button>
    </form>
</x-app-layout>
