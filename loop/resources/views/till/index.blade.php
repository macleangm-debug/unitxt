@php
    $shopCount = $shops->count();
    $defaultDial = $scanDial ?: \App\Support\Countries::dial($business->country ?? session('preferred_country', 'TZ'));
    $lockedShopId = $lockedShop?->id ?? null;
    $mustPickShop = empty($lockedShopId) && $shopCount > 1;
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
        @if ($lockedShopId)
            <input type="hidden" name="shop_id" value="{{ old('shop_id', $lockedShopId) }}">
            @if (! empty($isFrontDesk))
                <div class="rounded-xl border border-ink/10 bg-chalk/50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-muted">{{ __('loop.shop') }}</p>
                    <p class="mt-1 font-semibold text-ink">{{ $lockedShop->name }}</p>
                </div>
            @endif
        @elseif ($mustPickShop)
            <x-sheet-select
                name="shop_id"
                :label="__('loop.shop')"
                :options="$shops->mapWithKeys(fn ($s) => [$s->id => $s->name])->all()"
                :value="old('shop_id', '')"
                :required="true"
                :placeholder="__('loop.pick_shop_first')"
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
            <div x-data="loopQrScanner()" @loop-open-qr-scan.window="open()">
                <x-phone-field
                    name="phone"
                    :dial="$defaultDial"
                    hidden-dial-name="country_code"
                    :value="$scanPhone ?? old('phone')"
                    :required="true"
                    :autofocus="empty($scanPhone)"
                    :scanable="true"
                    x-ref="phoneInput"
                />
                <p class="mt-2 text-xs text-ink-muted">{{ __('loop.scan_or_type_phone') }}</p>

                <div
                    x-show="scanning"
                    x-cloak
                    class="fixed inset-0 z-[90] flex flex-col bg-ink"
                    @keydown.escape.window="close()"
                >
                    <div class="absolute inset-0">
                        <video x-ref="video" class="h-full w-full object-cover" playsinline muted></video>
                        <div class="absolute inset-0 bg-ink/25"></div>
                        <div class="pointer-events-none absolute inset-[12%] rounded-[1.5rem] border-2 border-lime/80 sm:inset-[18%]"></div>
                    </div>
                    <div class="relative z-10 flex items-center justify-between px-4 pb-2 pt-[max(1rem,env(safe-area-inset-top))]">
                        <div class="flex items-center gap-2">
                            <x-loop-logo class="h-8 w-8" />
                            <span class="font-display text-lg font-semibold tracking-tight text-white">Loop</span>
                        </div>
                        <button type="button" class="rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold text-white backdrop-blur" @click="close()">{{ __('loop.close') }}</button>
                    </div>
                    <div class="relative z-10 mt-auto px-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] text-center">
                        <p class="text-sm text-white/80" x-text="status || @js(__('loop.scan_member_qr_hint'))"></p>
                        <p x-show="error" class="mt-2 text-sm text-coral" x-text="error"></p>
                    </div>
                </div>
            </div>
        </div>
        <button class="loop-btn w-full">{{ __('loop.look_up') }}</button>
    </form>
</x-app-layout>
