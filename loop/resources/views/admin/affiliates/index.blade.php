@php
    $allowed = ['applications', 'approved', 'active', 'rejected', 'all', 'settings'];
    if (! in_array($tab, $allowed, true)) {
        $tab = 'applications';
    }
    $tabs = [
        'applications' => __('loop.affiliate_tab_applications').' ('.$counts['pending'].')',
        'approved' => __('loop.affiliate_tab_approved').' ('.$counts['approved'].')',
        'active' => __('loop.affiliate_tab_active').' ('.$counts['active'].')',
        'rejected' => __('loop.affiliate_tab_rejected').' ('.$counts['rejected'].')',
        'all' => __('loop.affiliate_tab_all').' ('.$counts['all'].')',
        'settings' => __('loop.affiliate_tab_settings'),
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_affiliates') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_affiliates_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <div class="loop-admin-tabs" role="tablist">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.affiliates.index', ['tab' => $key]) }}"
               class="loop-admin-tab {{ $tab === $key ? 'is-active' : '' }}"
               role="tab"
               aria-selected="{{ $tab === $key ? 'true' : 'false' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'settings')
        <form method="POST" action="{{ route('admin.affiliates.settings') }}" class="loop-glass space-y-4 p-6">
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
    @else
        @if ($tab === 'applications')
            <div class="mb-4 rounded-2xl bg-violet-soft/60 px-4 py-3 text-sm text-ink">
                <p class="font-semibold">{{ __('loop.affiliate_applications_queue') }}</p>
                <p class="mt-1 text-ink-muted">{{ __('loop.affiliate_applications_queue_blurb') }}</p>
            </div>
        @endif

        <div class="loop-table-wrap">
            <table class="loop-table">
                <thead>
                    <tr>
                        <th>{{ __('loop.name') }}</th>
                        <th>{{ __('loop.phone') }}</th>
                        <th>{{ __('loop.status') }}</th>
                        <th>{{ __('loop.when') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($affiliates as $row)
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $row->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $row->id_type }} · {{ $row->id_number }}</p>
                            </td>
                            <td>{{ $row->full_phone }}</td>
                            <td>
                                <span class="rounded-lg bg-chalk px-2 py-1 text-xs font-semibold">{{ __('loop.affiliate_status_'.$row->status) }}</span>
                            </td>
                            <td class="text-ink-muted">{{ $row->created_at?->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.affiliates.show', $row) }}" class="text-sm font-semibold text-violet">
                                    {{ $tab === 'applications' ? __('loop.review') : __('loop.view') }} →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-ink-muted">{{ __('loop.no_affiliates') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $affiliates->links() }}</div>
    @endif
</x-app-layout>
