<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_referrals') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.admin_referrals_blurb') }}</p>
    </x-slot>

    @include('admin.partials.nav')

    <div class="space-y-3">
        @forelse ($referrals as $referral)
            <div class="loop-panel flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                    <p class="font-semibold">{{ $referral->referrer?->name }} → {{ $referral->referred?->name }}</p>
                    <p class="mt-1 text-sm text-ink-muted">
                        {{ __('loop.ref_code') }}: {{ $referral->code_used }} · {{ $referral->status }}
                        @if ($referral->reward_type)
                            · {{ $referral->reward_type }} × {{ $referral->reward_value }}
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-ink-muted">{{ $referral->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($referral->isPending())
                        <form method="POST" action="{{ route('admin.referrals.qualify', $referral) }}">
                            @csrf
                            <button class="loop-btn-ghost !py-2">{{ __('loop.mark_qualified') }}</button>
                        </form>
                    @endif
                    @if (! $referral->isRewarded())
                        <form method="POST" action="{{ route('admin.referrals.reward', $referral) }}">
                            @csrf
                            <button class="loop-btn-mint !py-2">{{ __('loop.grant_reward') }}</button>
                        </form>
                    @else
                        <span class="rounded-lg bg-mint-soft px-3 py-1.5 text-xs font-semibold">{{ __('loop.rewarded') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-ink-muted">{{ __('loop.no_referrals_yet') }}</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $referrals->links() }}</div>
</x-app-layout>
