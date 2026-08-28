<x-admin-layout>
    @php
        $idLabel = [
            'national_id' => __('loop.id_national'),
            'nida' => __('loop.id_national'),
            'passport' => __('loop.id_passport'),
            'drivers_license' => __('loop.id_drivers'),
            'voter_id' => __('loop.id_voter'),
        ][$affiliate->id_type] ?? $affiliate->id_type;
        $countryName = \App\Support\Countries::OPTIONS[$affiliate->country]['name'] ?? $affiliate->country;
    @endphp
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
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.application_review_help') }}</p>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.first_name') }}</dt><dd class="font-semibold">{{ $affiliate->first_name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.last_name') }}</dt><dd class="font-semibold">{{ $affiliate->last_name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.country') }}</dt><dd class="font-semibold">{{ $countryName }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.phone') }}</dt><dd class="font-semibold">{{ $affiliate->full_phone }}</dd></div>
                @if ($affiliate->email)
                    <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.email') }}</dt><dd class="font-semibold">{{ $affiliate->email }}</dd></div>
                @endif
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.city') }}</dt><dd class="font-semibold">{{ $affiliate->city ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.district') }}</dt><dd class="font-semibold">{{ $affiliate->district ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.street') }}</dt><dd class="font-semibold">{{ $affiliate->address ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.id_type') }}</dt><dd class="font-semibold">{{ $idLabel }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.id_number') }}</dt><dd class="font-semibold">{{ $affiliate->id_number }}</dd></div>
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

                    <x-loop-sheet model="open">
                            <p class="font-display text-2xl font-semibold" x-text="decision==='approved' ? @js(__('loop.confirm_approve_title')) : @js(__('loop.confirm_reject_title'))"></p>
                            <p class="mt-2 text-sm text-ink-muted" x-text="decision==='approved' ? @js(__('loop.confirm_approve_body')) : @js(__('loop.confirm_reject_body'))"></p>
                            <button type="submit" form="affiliate-decide-form" class="admin-btn mt-6 w-full" x-text="decision==='approved' ? @js(__('loop.approve')) : @js(__('loop.reject'))"></button>
                            <button type="button" class="mt-3 text-sm font-semibold text-ink-muted" @click="open=false">{{ __('loop.cancel') }}</button>
                    </x-loop-sheet>
                </div>
            @else
                <p class="mt-4 text-sm text-ink-muted">{{ __('loop.reviewed_on') }} {{ $affiliate->reviewed_at?->format('d M Y H:i') }}</p>
                @if ($affiliate->decision_note)
                    <p class="mt-3 rounded-2xl bg-chalk px-4 py-3 text-sm">{{ $affiliate->decision_note }}</p>
                @endif
            @endif
        </section>
    </div>

    @if (! $affiliate->isPending())
        <section class="mt-8 rounded-[2rem] border border-ink/8 bg-white/95 p-6">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.affiliate_next_in_process') }}</h2>
            @if ($affiliate->status === 'approved')
                <ol class="mt-4 space-y-2.5 text-sm text-ink">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-700">1</span>
                        <span>{{ __('loop.affiliate_next_activate') }}</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-700">2</span>
                        <span>{{ __('loop.affiliate_next_promo') }}</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-700">3</span>
                        <span>{{ __('loop.affiliate_next_payout') }}</span>
                    </li>
                </ol>
            @endif
            @if (in_array($affiliate->status, ['approved', 'active'], true) && ($affiliate->tracking_code || $affiliate->promo_code || $affiliate->payout_phone))
                <dl class="mt-5 space-y-3 text-sm">
                    @if ($affiliate->tracking_code)
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.tracking_code') }}</dt><dd class="font-semibold">{{ $affiliate->tracking_code }}</dd></div>
                    @endif
                    @if ($affiliate->promo_code)
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.promo_code') }}</dt><dd class="font-semibold">{{ $affiliate->promo_code }}</dd></div>
                    @endif
                    @if ($affiliate->payout_phone)
                        <div class="flex justify-between gap-3"><dt class="text-ink-muted">{{ __('loop.payout_phone') }}</dt><dd class="font-semibold">{{ $affiliate->payout_phone }}</dd></div>
                    @endif
                </dl>
            @endif
        </section>
    @endif

    @if ($affiliate->status === 'active')
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
    @endif
</x-admin-layout>
