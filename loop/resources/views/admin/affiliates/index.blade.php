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
            <a href="{{ $key === 'performance' ? route('admin.insights.affiliate-performance') : route('admin.affiliates.index', ['tab' => $key]) }}"
               class="loop-admin-tab {{ $tab === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'settings')
        <div class="loop-glass p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.affiliate_program_settings') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.affiliate_program_settings_blurb') }}</p>
            <form method="POST" action="{{ route('admin.affiliates.settings') }}" class="mt-4">
                @csrf
                @method('PUT')
                <x-admin.settings-lock>
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
                            <label class="loop-label">{{ __('loop.cookie_days') }}</label>
                            <input type="number" min="1" max="365" name="cookie_days" value="{{ old('cookie_days', $settings['cookie_days']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.pin_length') }}</label>
                            <input type="number" min="4" max="6" name="pin_length" value="{{ old('pin_length', $settings['pin_length']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.monthly_paying_target') }}</label>
                            <input type="number" min="1" max="100" name="monthly_paying_business_target" value="{{ old('monthly_paying_business_target', $settings['monthly_paying_business_target']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.min_payout_amount') }}</label>
                            <input type="number" min="0" name="min_payout_amount" value="{{ old('min_payout_amount', $settings['min_payout_amount']) }}" class="loop-input" required>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.payout_schedule') }}</label>
                            <select name="payout_schedule" class="loop-input">
                                @foreach (['weekly', 'biweekly', 'monthly'] as $sched)
                                    <option value="{{ $sched }}" @selected(old('payout_schedule', $settings['payout_schedule']) === $sched)>{{ __("loop.payout_$sched") }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.tax_withholding_percent') }}</label>
                            <input type="number" min="0" max="40" name="tax_withholding_percent" value="{{ old('tax_withholding_percent', $settings['tax_withholding_percent']) }}" class="loop-input">
                        </div>
                        <div>
                            <label class="loop-label">{{ __('loop.fraud_hold_days') }}</label>
                            <input type="number" min="0" max="90" name="fraud_hold_days" value="{{ old('fraud_hold_days', $settings['fraud_hold_days']) }}" class="loop-input">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="loop-label">{{ __('loop.terms_url') }}</label>
                            <input type="url" name="terms_url" value="{{ old('terms_url', $settings['terms_url']) }}" class="loop-input">
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))> {{ __('loop.affiliate_program_enabled') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="attribution_enabled" value="1" @checked(old('attribution_enabled', $settings['attribution_enabled']))> {{ __('loop.attribution_enabled') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="kpi_enabled" value="1" @checked(old('kpi_enabled', $settings['kpi_enabled']))> {{ __('loop.kpi_enabled') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="show_kpis_to_affiliates" value="1" @checked(old('show_kpis_to_affiliates', $settings['show_kpis_to_affiliates']))> {{ __('loop.show_kpis_to_affiliates') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="block_self_referral" value="1" @checked(old('block_self_referral', $settings['block_self_referral']))> {{ __('loop.block_self_referral') }}</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="require_tax_id" value="1" @checked(old('require_tax_id', $settings['require_tax_id']))> {{ __('loop.require_tax_id') }}</label>
                    </div>
                    <button class="loop-btn-mint">{{ __('loop.save') }}</button>
                </x-admin.settings-lock>
            </form>
        </div>
    @else
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
    @endif
</x-app-layout>
