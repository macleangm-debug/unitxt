<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_affiliates') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $affiliate->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $affiliate->full_phone }} · {{ __('loop.affiliate_status_'.$affiliate->status) }}</p>
            </div>
            <a href="{{ route('admin.affiliates.index') }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-[2rem] border border-ink/8 bg-white/95 p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.application_details') }}</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.email') }}</dt><dd class="font-semibold">{{ $affiliate->email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.id_type') }}</dt><dd class="font-semibold">{{ [
                    'national_id' => __('loop.id_national'),
                    'passport' => __('loop.id_passport'),
                    'drivers_license' => __('loop.id_drivers'),
                    'voter_id' => __('loop.id_voter'),
                ][$affiliate->id_type] ?? $affiliate->id_type }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.id_number') }}</dt><dd class="font-semibold">{{ $affiliate->id_number }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.city') }}</dt><dd class="font-semibold">{{ $affiliate->city }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.payout_phone') }}</dt><dd class="font-semibold">{{ $affiliate->payout_phone ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.tracking_code') }}</dt><dd class="font-semibold">{{ $affiliate->tracking_code ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.promo_code') }}</dt><dd class="font-semibold">{{ $affiliate->promo_code ?: '—' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-[2rem] border border-ink/8 bg-white/95 p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.decision_tab') }}</h2>
            @if ($affiliate->isPending())
                <form method="POST" action="{{ route('admin.affiliates.decide', $affiliate) }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="loop-label">{{ __('loop.decision_note') }}</label>
                        <textarea name="decision_note" rows="3" class="loop-input">{{ old('decision_note') }}</textarea>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <button name="decision" value="approved" class="loop-btn-mint w-full">{{ __('loop.approve') }}</button>
                        <button name="decision" value="rejected" class="loop-btn-ghost w-full">{{ __('loop.reject') }}</button>
                    </div>
                </form>
            @else
                <p class="mt-4 text-sm text-ink-muted">{{ __('loop.reviewed_on') }} {{ $affiliate->reviewed_at?->format('d M Y H:i') }}</p>
                @if ($affiliate->decision_note)
                    <p class="mt-3 rounded-2xl bg-chalk px-4 py-3 text-sm">{{ $affiliate->decision_note }}</p>
                @endif
            @endif
        </section>
    </div>

    <section class="mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.referrals') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($affiliate->referrals as $row)
                <div class="rounded-[1.25rem] border border-ink/8 bg-white px-4 py-3">
                    <p class="font-semibold">{{ $row->business->name }}</p>
                    <p class="text-sm text-ink-muted">{{ __('loop.affiliate_ref_status_'.$row->status) }} · {{ number_format($row->commission_amount) }}</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted">{{ __('loop.no_affiliate_referrals') }}</p>
            @endforelse
        </div>
    </section>
</x-app-layout>
