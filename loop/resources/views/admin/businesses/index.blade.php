<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_businesses') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_businesses_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <form method="GET" class="mb-6 flex gap-2">
        <input type="search" name="q" value="{{ $q }}" placeholder="{{ __('loop.search_businesses') }}" class="loop-input max-w-md">
        <button class="loop-btn-mint !py-2">{{ __('loop.apply') }}</button>
    </form>

    <div class="space-y-4">
        @foreach ($businesses as $business)
            <div class="loop-panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-display text-lg font-semibold">{{ $business->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ $business->owner?->name }} · {{ $business->owner?->full_phone }}
                        </p>
                        <p class="mt-1 text-xs text-ink-muted">
                            {{ $business->shops_count }} {{ __('loop.shops') }} ·
                            {{ $business->memberships_count }} {{ __('loop.members') }} ·
                            {{ $business->visits_count }} {{ __('loop.sales') }} ·
                            {{ __('loop.ref_code') }}: <span class="font-semibold text-ink">{{ $business->referral_code }}</span>
                            · {{ $business->referrals_made_count }} {{ __('loop.referrals') }}
                        </p>
                    </div>
                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $business->is_active ? 'bg-mint-soft text-ink' : 'bg-coral/20 text-ink' }}">
                        {{ $business->is_active ? __('loop.live') : __('loop.off') }}
                    </span>
                </div>

                <form method="POST" action="{{ route('admin.businesses.update', $business) }}" class="mt-4 grid gap-3 sm:grid-cols-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="loop-label">{{ __('loop.plan') }}</label>
                        <select name="plan_key" class="loop-input">
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->key }}" @selected($business->plan_key === $plan->key)>{{ $plan->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="loop-label">{{ __('loop.billing_status') }}</label>
                        <select name="billing_status" class="loop-input">
                            @foreach (['trialing', 'active', 'free', 'past_due', 'suspended'] as $status)
                                <option value="{{ $status }}" @selected($business->billing_status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm font-semibold">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-ink/20 text-mint focus:ring-mint" @checked($business->is_active)>
                            {{ __('loop.active') }}
                        </label>
                    </div>
                    <div class="flex items-end">
                        <button class="loop-btn-ghost w-full !py-2">{{ __('loop.save') }}</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $businesses->links() }}</div>
</x-app-layout>
