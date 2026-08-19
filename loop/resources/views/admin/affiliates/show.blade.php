<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.admin_affiliates') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $affiliate->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $affiliate->full_phone }} · {{ __('loop.affiliate_status_'.$affiliate->status) }}</p>
            </div>
            <a href="{{ route('admin.affiliates.index') }}" class="admin-btn-ghost !py-2">{{ __('loop.back') }}</a>
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
                <div x-data="{ open: false, decision: 'approved' }" class="mt-5 space-y-4">
                    <form method="POST" action="{{ route('admin.affiliates.decide', $affiliate) }}" id="affiliate-decide-form">
                        @csrf
                        <div>
                            <label class="loop-label">{{ __('loop.decision_note') }}</label>
                            <textarea name="decision_note" rows="3" class="loop-input">{{ old('decision_note') }}</textarea>
                        </div>
                        <input type="hidden" name="decision" :value="decision">
                    </form>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <button type="button" class="admin-btn w-full" @click="decision='approved'; open=true">{{ __('loop.approve') }}</button>
                        <button type="button" class="admin-btn-ghost w-full" @click="decision='rejected'; open=true">{{ __('loop.reject') }}</button>
                    </div>

                    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
                        <div class="absolute inset-0 bg-ink/55 backdrop-blur-sm" @click="open=false"></div>
                        <div class="relative w-full max-w-md rounded-[2rem] bg-white p-8 text-center shadow-2xl">
                            <p class="font-display text-2xl font-semibold" x-text="decision==='approved' ? @js(__('loop.confirm_approve_title')) : @js(__('loop.confirm_reject_title'))"></p>
                            <p class="mt-2 text-sm text-ink-muted" x-text="decision==='approved' ? @js(__('loop.confirm_approve_body')) : @js(__('loop.confirm_reject_body'))"></p>
                            <button type="submit" form="affiliate-decide-form" class="admin-btn mt-6 w-full" x-text="decision==='approved' ? @js(__('loop.approve')) : @js(__('loop.reject'))"></button>
                            <button type="button" class="mt-3 text-sm font-semibold text-ink-muted" @click="open=false">{{ __('loop.cancel') }}</button>
                        </div>
                    </div>
                </div>
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
</x-admin-layout>
