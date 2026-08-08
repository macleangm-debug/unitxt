<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.affiliates') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.affiliate_dashboard') }}</h1>
                <p class="mt-1 text-ink-muted">{{ $affiliate->name }}</p>
            </div>
            <div class="text-right text-xs text-ink-muted">
                <p>{{ __('loop.tracking_code') }}</p>
                <p class="mt-1 font-semibold text-ink">{{ $affiliate->tracking_code }}</p>
            </div>
        </div>
    </x-slot>

    <section
        class="loop-wallet p-6 sm:p-8"
        x-data="{
            code: @js($affiliate->promo_code),
            url: @js($shareUrl),
            text: @js($shareText),
            copied: false,
            copy(value) {
                navigator.clipboard.writeText(value);
                this.copied = true;
                setTimeout(() => this.copied = false, 1800);
            }
        }"
    >
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-lime">{{ __('loop.start_sharing') }}</p>
        <p class="mt-3 font-display text-4xl font-semibold tracking-tight text-lime" x-text="code"></p>
        <p class="mt-2 max-w-xl text-sm text-white/70">{{ __('loop.share_promo_blurb') }}</p>

        <div class="mt-6 flex flex-wrap gap-2">
            <button type="button" class="loop-btn-lime !py-2.5" @click="copy(code)">{{ __('loop.copy_code') }}</button>
            <button type="button" class="rounded-2xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/15" @click="copy(url)">{{ __('loop.copy_link') }}</button>
            <button type="button" class="rounded-2xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/15"
                    @click="
                        if (navigator.share) { navigator.share({ title: 'Loop', text, url }); }
                        else { copy(text); }
                    ">{{ __('loop.share_now') }}</button>
        </div>
        <p class="mt-3 text-xs text-lime" x-show="copied" x-cloak>{{ __('loop.copied') }}</p>
        <input type="hidden" :value="url" id="share-url">
    </section>

    <section class="mt-8 rounded-[2rem] border border-ink/8 bg-white/95 p-6 sm:p-8">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.customize_promo') }}</h2>
        <p class="mt-1 text-sm text-ink-muted">{{ __('loop.customize_promo_body') }}</p>
        <form method="POST" action="{{ route('affiliate.promo.update') }}" class="mt-5 flex flex-wrap gap-3">
            @csrf
            @method('PUT')
            <input name="promo_code" value="{{ old('promo_code', $affiliate->promo_code) }}" class="loop-input max-w-xs uppercase" minlength="4" maxlength="12" pattern="[A-Za-z0-9]+" required>
            <button class="loop-btn-mint !py-2.5">{{ __('loop.save_promo') }}</button>
        </form>
        <x-input-error :messages="$errors->get('promo_code')" class="mt-2" />
    </section>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[1.5rem] bg-mint-soft p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.signups') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['signups'] }}</p>
        </div>
        <div class="rounded-[1.5rem] border border-ink/8 bg-white p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.qualified') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['qualified'] }}</p>
        </div>
        <div class="rounded-[1.5rem] border border-ink/8 bg-white p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.earned') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ number_format($stats['earned']) }}</p>
        </div>
        <div class="rounded-[1.5rem] border border-ink/8 bg-white p-5">
            <p class="text-xs text-ink-muted">{{ __('loop.pending') }}</p>
            <p class="mt-2 font-display text-3xl font-semibold">{{ $stats['pending'] }}</p>
        </div>
    </div>

    <section class="mt-10">
        <h2 class="font-display text-xl font-semibold">{{ __('loop.your_referrals') }}</h2>
        <div class="mt-4 space-y-3">
            @forelse ($referrals as $row)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4">
                    <div>
                        <p class="font-semibold">{{ $row->business->name }}</p>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ __('loop.affiliate_ref_status_'.$row->status) }}
                            @if ($row->attribution_ends_at)
                                · {{ __('loop.attribution_until') }} {{ $row->attribution_ends_at->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                    <p class="font-display text-xl font-semibold">{{ number_format($row->commission_amount) }}</p>
                </div>
            @empty
                <div class="rounded-[1.5rem] border border-dashed border-ink/15 bg-chalk/40 px-5 py-8 text-center">
                    <p class="font-display text-lg font-semibold">{{ __('loop.no_affiliate_referrals') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('loop.share_to_get_first') }}</p>
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
