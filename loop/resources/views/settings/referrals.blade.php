@php
    $youDays = (int) ($program['referrer_extra_days_per_referral'] ?? 3);
    $theyDays = (int) ($program['referred_extra_trial_days'] ?? 5);
    $goal = (int) ($progress['goal'] ?? $program['goal_count'] ?? 3);
    $joined = (int) ($progress['joined'] ?? 0);
    $percent = (int) ($progress['percent'] ?? 0);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-violet">{{ __('loop.referrals') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight">{{ __('loop.referral_page_title') }}</h1>
                <p class="mt-1 max-w-lg text-sm text-ink-muted">{{ __('loop.referral_page_blurb', ['days' => $youDays]) }}</p>
            </div>
            <x-settings-back />
        </div>
    </x-slot>

    <div class="overflow-hidden rounded-[1.75rem] border border-ink/10 bg-gradient-to-br from-ink via-[#1a1430] to-violet p-5 text-white shadow-[0_24px_60px_rgba(17,17,20,0.16)] sm:p-7">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.your_invite') }}</p>
                <p class="mt-2 font-mono text-2xl font-semibold tracking-[0.14em] text-lime sm:text-3xl">{{ $code }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-right backdrop-blur-sm">
                <p class="text-xs text-white/65">{{ __('loop.extra_days_earned') }}</p>
                <p class="font-display text-3xl font-semibold">{{ $business->referral_credit_days }}</p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:items-center" x-data="{ copied: false }">
            <input readonly value="{{ $shareUrl }}" class="w-full rounded-2xl border border-white/15 bg-white/10 px-4 py-3 font-mono text-xs text-white/90 focus:border-lime focus:outline-none sm:text-sm" id="ref-link">
            <div class="flex shrink-0 gap-2">
                <button type="button" class="rounded-2xl border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white hover:bg-white/15"
                        @click="navigator.clipboard.writeText(@js($shareUrl)); copied=true; setTimeout(() => copied=false, 1800)">
                    <span x-text="copied ? @js(__('loop.copied')) : @js(__('loop.copy'))"></span>
                </button>
                <button type="button" class="inline-flex items-center gap-2 rounded-2xl bg-lime px-4 py-3 text-sm font-semibold text-ink hover:bg-white"
                        @click="
                            if (navigator.share) {
                                navigator.share({ title: 'Loop', text: @js(__('loop.share_invite_text')), url: @js($shareUrl) });
                            } else {
                                navigator.clipboard.writeText(@js($shareUrl));
                                copied = true;
                                setTimeout(() => copied = false, 1800);
                            }
                        ">
                    {{ __('loop.share') }}
                </button>
            </div>
        </div>

        <div class="mt-6">
            <div class="mb-2 flex items-center justify-between text-xs text-white/70">
                <span>{{ __('loop.referral_joined_count', ['count' => $joined, 'goal' => $goal]) }}</span>
                <span>{{ $percent }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-white/15">
                <div class="h-full rounded-full bg-lime transition-all" style="width: {{ $percent }}%"></div>
            </div>
        </div>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2">
        <div class="rounded-[1.5rem] border border-violet/20 bg-gradient-to-br from-violet-soft/80 to-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.you_get') }}</p>
            <p class="mt-2 font-display text-4xl font-semibold text-ink">+{{ $youDays }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.you_get_days_label') }}</p>
        </div>
        <div class="rounded-[1.5rem] border border-lime/40 bg-gradient-to-br from-lime/30 to-white p-5">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.they_get') }}</p>
            <p class="mt-2 font-display text-4xl font-semibold text-ink">+{{ $theyDays }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.they_get_days_label') }}</p>
        </div>
    </div>

    <section class="mt-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.connected_referrals') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.connected_referrals_blurb') }}</p>
        <div class="mt-4 space-y-3">
            @forelse ($referrals as $referral)
                <div class="flex items-center justify-between gap-3 rounded-[1.35rem] border border-ink/8 bg-white/90 px-5 py-4">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $referral->referred?->name }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ $referral->qualified_at?->diffForHumans() ?? $referral->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="shrink-0 rounded-lg bg-lime/40 px-2.5 py-1 text-xs font-semibold text-ink">{{ __('loop.on_loop') }}</span>
                </div>
            @empty
                <div class="rounded-[1.35rem] border border-dashed border-ink/15 px-5 py-8 text-center text-sm text-ink-muted">
                    {{ __('loop.no_connected_referrals') }}
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
