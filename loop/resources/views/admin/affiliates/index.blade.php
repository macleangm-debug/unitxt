@php
    $allowed = ['applications', 'approved', 'active', 'rejected', 'all'];
    if (! in_array($tab, $allowed, true)) {
        $tab = 'applications';
    }
    $tabs = [
        'applications' => __('loop.affiliate_tab_applications').' ('.$counts['pending'].')',
        'approved' => __('loop.affiliate_tab_approved').' ('.$counts['approved'].')',
        'active' => __('loop.affiliate_tab_active').' ('.$counts['active'].')',
        'rejected' => __('loop.affiliate_tab_rejected').' ('.$counts['rejected'].')',
        'all' => __('loop.affiliate_tab_all').' ('.$counts['all'].')',
        'performance' => __('loop.affiliate_tab_performance'),
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
            <a href="{{ $key === 'performance'
                    ? route('admin.insights.affiliate-performance')
                    : ($key === 'settings'
                        ? route('admin.settings', ['tab' => 'affiliates'])
                        : route('admin.affiliates.index', ['tab' => $key])) }}"
               class="loop-admin-tab {{ $tab === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-mint-soft/50 px-4 py-3 text-sm text-ink">
        <div>
            <p class="font-semibold">{{ __('loop.settings_source_of_truth') }}</p>
            <p class="mt-1 text-ink-muted">
                {{ __('loop.affiliate_settings_managed_in_hub') }}
                · {{ __('loop.commission_percent') }} {{ $settings['commission_percent'] }}%
                · {{ __('loop.monthly_paying_target') }} {{ $settings['monthly_paying_business_target'] }}
            </p>
        </div>
        <a href="{{ route('admin.settings', ['tab' => 'affiliates']) }}" class="loop-btn-mint !py-2">{{ __('loop.edit_in_settings_hub') }} →</a>
    </div>

    @if ($tab === 'applications')
        <div class="mb-4 rounded-2xl bg-violet-soft/60 px-4 py-3 text-sm text-ink">
            <p class="font-semibold">{{ __('loop.affiliate_applications_queue') }}</p>
            <p class="mt-1 text-ink-muted">{{ __('loop.affiliate_applications_queue_blurb') }}</p>
        </div>
    @endif

    <x-admin.empty-state :empty="$affiliates->isEmpty()" :title="__('loop.admin_affiliates')">
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
                    @foreach ($affiliates as $row)
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
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $affiliates->links() }}</div>
    </x-admin.empty-state>
</x-app-layout>
