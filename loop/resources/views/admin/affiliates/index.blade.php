<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_affiliates') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_affiliates_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <form method="POST" action="{{ route('admin.affiliates.settings') }}" class="mb-8 space-y-4 rounded-[2rem] border border-ink/10 bg-white/90 p-6">
        @csrf
        @method('PUT')
        <div>
            <h2 class="font-display text-xl font-semibold">{{ __('loop.affiliate_program_settings') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.affiliate_program_settings_blurb') }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="loop-label">{{ __('loop.commission_percent') }}</label>
                <input type="number" min="1" max="50" name="commission_percent" value="{{ old('commission_percent', $settings['commission_percent']) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.referred_discount_percent') }}</label>
                <input type="number" min="0" max="50" name="referred_discount_percent" value="{{ old('referred_discount_percent', $settings['referred_discount_percent']) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.attribution_months') }}</label>
                <input type="number" min="1" max="36" name="attribution_months" value="{{ old('attribution_months', $settings['attribution_months']) }}" class="loop-input" required>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.pin_length') }}</label>
                <input type="number" min="4" max="6" name="pin_length" value="{{ old('pin_length', $settings['pin_length']) }}" class="loop-input" required>
            </div>
        </div>
        <div class="flex flex-wrap gap-4 text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))> {{ __('loop.affiliate_program_enabled') }}</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="attribution_enabled" value="1" @checked(old('attribution_enabled', $settings['attribution_enabled']))> {{ __('loop.attribution_enabled') }}</label>
        </div>
        <button class="loop-btn-mint">{{ __('loop.save') }}</button>
    </form>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['pending', 'approved', 'active', 'rejected', 'all'] as $t)
            <a href="{{ route('admin.affiliates.index', ['tab' => $t]) }}"
               class="rounded-xl px-3 py-2 text-sm font-semibold {{ $tab === $t ? 'bg-ink text-white' : 'bg-white text-ink-muted ring-1 ring-ink/10' }}">
                {{ __('loop.affiliate_tab_'.$t) }} ({{ $counts[$t] }})
            </a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($affiliates as $row)
            <a href="{{ route('admin.affiliates.show', $row) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4 transition hover:-translate-y-0.5">
                <div>
                    <p class="font-display text-lg font-semibold">{{ $row->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ $row->full_phone }} · {{ $row->id_type }} · {{ $row->id_number }}</p>
                </div>
                <span class="rounded-full bg-chalk px-3 py-1 text-xs font-semibold">{{ __('loop.affiliate_status_'.$row->status) }}</span>
            </a>
        @empty
            <p class="text-sm text-ink-muted">{{ __('loop.no_affiliates') }}</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $affiliates->links() }}</div>
</x-app-layout>
