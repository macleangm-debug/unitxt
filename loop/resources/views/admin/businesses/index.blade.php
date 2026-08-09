<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_businesses') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_businesses_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <form method="GET" class="mb-6 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('loop.search_businesses') }}" class="loop-input max-w-md !mt-0">
        <button class="loop-btn-mint !py-2.5">{{ __('loop.apply') }}</button>
    </form>

    <div class="loop-table-wrap">
        <table class="loop-table">
            <thead>
                <tr>
                    <th>{{ __('loop.business') }}</th>
                    <th>{{ __('loop.sector') }}</th>
                    <th>{{ __('loop.plan') }}</th>
                    <th>{{ __('loop.billing_status') }}</th>
                    <th>{{ __('loop.shops') }}</th>
                    <th>{{ __('loop.members') }}</th>
                    <th>{{ __('loop.sales') }}</th>
                    <th>{{ __('loop.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($businesses as $business)
                    <tr>
                        <td>
                            <p class="font-semibold">{{ $business->name }}</p>
                            <p class="text-xs text-ink-muted">{{ $business->owner?->name }} · {{ $business->city }}</p>
                            @if (! empty($abuseFlags[$business->id]))
                                <p class="mt-1 text-[11px] font-semibold text-coral">{{ __('loop.admin_multi_branch_flag') }}</p>
                            @endif
                        </td>
                        <td>{{ \App\Support\Sectors::label($business->sector, $business->sector_other) }}</td>
                        <td class="capitalize">{{ $business->plan_key }}</td>
                        <td class="capitalize">{{ $business->billing_status }}</td>
                        <td>{{ $business->shops_count }}</td>
                        <td>{{ $business->memberships_count }}</td>
                        <td>{{ $business->visits_count }}</td>
                        <td>
                            <span class="rounded-lg px-2 py-1 text-xs font-semibold {{ $business->is_active ? 'bg-mint-soft text-ink' : 'bg-coral/20 text-ink' }}">
                                {{ $business->is_active ? __('loop.live') : __('loop.off') }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.businesses.show', $business) }}" class="text-sm font-semibold text-violet">{{ __('loop.view') }} →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 text-ink-muted">{{ __('loop.no_data_yet') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $businesses->links() }}</div>
</x-app-layout>
