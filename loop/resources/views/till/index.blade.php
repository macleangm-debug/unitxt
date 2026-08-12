@php
    $shopCount = $shops->count();
    $defaultDial = $scanDial ?: \App\Support\Countries::dial($business->country ?? session('preferred_country', 'TZ'));
    $hasRecent = ! empty($showRecent) && isset($recent) && $recent->count() > 0;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="loop-wallet  mb-2 px-5 py-6 sm:px-7 sm:py-7" x-data="loopLivingWallet()">
            <div class="loop-orb loop-orb--a "></div>
            <div class="loop-orb loop-orb--b "></div>
            <div class="relative">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-lime">Loop</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('loop.sale') }}</h1>
                <p class="mt-1 text-sm text-white/60">{{ __('loop.sale_blurb_short') }}</p>
                @if (! empty($scanPhone))
                    <p class="mt-2 text-sm font-semibold text-lime">{{ __('loop.wallet_qr_scanned') }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    @if (! empty($tillLocked))
        <div class="mb-6 max-w-xl rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-4">
            <p class="font-display text-lg font-semibold">{{ __('loop.till_locked_title') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.till_locked_body') }}</p>
            @if (! empty($isOwner))
                <a href="{{ route('billing.show') }}" class="mt-4 inline-flex rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white hover:bg-black">{{ __('loop.upgrade_now') }}</a>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('till.lookup') }}" class="loop-panel mx-auto max-w-xl space-y-4 p-6 {{ ! empty($tillLocked) ? 'pointer-events-none opacity-50' : '' }}">
        @csrf
        @if ($shopCount > 1)
            <x-sheet-select
                name="shop_id"
                :label="__('loop.shop')"
                :options="$shops->mapWithKeys(fn ($s) => [$s->id => $s->name])->all()"
                :value="old('shop_id', $shops->first()?->id)"
                :required="true"
            />
        @else
            <input type="hidden" name="shop_id" value="{{ $shops->first()?->id }}">
        @endif

        <div>
            <label class="loop-label">{{ __('loop.channel') }}</label>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <label class="rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft">
                    <input type="radio" name="channel" value="in_store" class="sr-only" checked> {{ __('loop.in_store') }}
                </label>
                <label class="rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft">
                    <input type="radio" name="channel" value="phone_order" class="sr-only"> {{ __('loop.phone_order') }}
                </label>
            </div>
        </div>

        <div>
            <label class="loop-label">{{ __('loop.customer_phone') }}</label>
            <x-phone-field
                name="phone"
                :dial="$defaultDial"
                hidden-dial-name="country_code"
                :value="$scanPhone ?? old('phone')"
                :required="true"
                :autofocus="empty($scanPhone)"
            />
        </div>
        <button class="loop-btn w-full">{{ __('loop.look_up') }}</button>
    </form>

    @if ($hasRecent)
        <section class="mx-auto mt-10 max-w-xl">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_till') }}</h2>
                <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }} →</a>
            </div>
            <div class="space-y-3">
                @foreach ($recent as $visit)
                    <div class="loop-panel flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $visit->customer->name }} · {{ $visit->shop->name }}</p>
                            <p class="text-sm text-ink-muted">
                                {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                                · {{ $visit->channel === 'phone_order' ? __('loop.phone_order') : __('loop.in_store') }}
                                · {{ $visit->created_at->format('d M Y · H:i') }}
                            </p>
                        </div>
                        <span class="rounded-lg bg-mint-soft px-2.5 py-1 text-sm font-semibold text-mint-deep">+{{ $visit->points_earned }} {{ __('loop.pts') }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
