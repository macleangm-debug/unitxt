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
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('loop.whos_buying') }}</h1>
                <p class="mt-1 text-sm text-white/60">{{ __('loop.sale_blurb_short') }}</p>
                @if (! empty($scanPhone))
                    <p class="mt-2 text-sm font-semibold text-lime">{{ __('loop.wallet_qr_scanned') }}</p>
                @endif
            </div>
        </div>
    </x-slot>

    @if (! empty($loopPaused))
        <div class="mb-6 max-w-xl rounded-[1.5rem] border border-coral/30 bg-coral/10 px-5 py-4">
            <p class="font-display text-lg font-semibold">{{ __('loop.loop_paused_till_title') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.till_paused_body') }}</p>
            @if (! empty($isOwner))
                <a href="{{ route('billing.show') }}" class="mt-4 inline-flex rounded-xl bg-ink px-4 py-2.5 text-sm font-semibold text-white hover:bg-black">{{ __('loop.reactivate_loop') }}</a>
            @endif
        </div>
    @endif

    @if ($shopCount < 1)
        <div class="loop-panel mx-auto max-w-xl p-6">
            <p class="rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm">{{ __('loop.till_needs_shop') }}</p>
        </div>
    @elseif (! empty($needsBranchPick))
        <div class="loop-panel mx-auto max-w-xl space-y-3 p-6">
            <div>
                <p class="loop-label">{{ __('loop.choose_branch') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.choose_branch_first_blurb') }}</p>
            </div>
            @foreach ($shops as $shop)
                <form method="POST" action="{{ route('till.branch') }}">
                    @csrf
                    <input type="hidden" name="shop_id" value="{{ $shop->id }}">
                    @if (! empty($scanQuery))
                        <input type="hidden" name="scan" value="{{ $scanQuery }}">
                    @endif
                    <button type="submit" class="flex w-full items-center justify-between rounded-2xl border border-ink/10 bg-white px-4 py-4 text-left hover:border-mint">
                        <span class="font-semibold">{{ $shop->name }}</span>
                        <span class="text-sm text-ink-muted">{{ $shop->city }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    @else
    <form method="POST" action="{{ route('till.lookup') }}" class="loop-panel mx-auto max-w-xl space-y-4 p-6">
        @csrf
        @if ($errors->any())
            <div class="rounded-xl border border-coral/30 bg-coral/10 px-4 py-3 text-sm text-ink" role="alert">
                {{ $errors->first() }}
            </div>
        @endif
            <input type="hidden" name="shop_id" value="{{ $activeShop->id }}">
            <div class="flex items-center justify-between rounded-2xl border border-ink/10 bg-chalk/50 px-4 py-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-mint-deep">{{ __('loop.selling_at') }}</p>
                    <p class="font-semibold">{{ $activeShop->name }}</p>
                </div>
                @if ($shopCount > 1)
                    <a href="{{ route('till.index', array_filter(['change' => 1, 'scan' => $scanQuery ?? null])) }}" class="text-sm font-semibold text-violet">{{ __('loop.change_branch') }}</a>
                @endif
            </div>

        <div>
            <p class="loop-label">{{ __('loop.enter_phone_or_scan') }}</p>
            <div
                x-data="loopQrScanner({
                    scanningLabel: @js(__('loop.scanning')),
                    secureError: @js(__('loop.scan_camera_https')),
                    cameraError: @js(__('loop.scan_camera_unavailable')),
                    unrecognized: @js(__('loop.scan_qr_unrecognized')),
                })"
                @loop-open-qr-scan.window="open()"
            >
                <x-phone-field
                    name="phone"
                    :dial="$defaultDial"
                    hidden-dial-name="country_code"
                    :value="$scanPhone ?? old('phone')"
                    :required="true"
                    :autofocus="empty($scanPhone)"
                    :scanable="true"
                />
                <p class="mt-2 text-xs text-ink-muted">{{ __('loop.scan_or_type_phone') }}</p>
                <details class="mt-3">
                    <summary class="cursor-pointer text-xs font-semibold text-ink-muted">{{ __('loop.phone_order') }} / {{ __('loop.in_store') }}</summary>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <label class="rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft">
                            <input type="radio" name="channel" value="in_store" class="sr-only" @checked(($channel ?? 'in_store') !== 'phone_order')> {{ __('loop.in_store') }}
                        </label>
                        <label class="rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint-deep has-[:checked]:bg-mint-soft">
                            <input type="radio" name="channel" value="phone_order" class="sr-only" @checked(($channel ?? 'in_store') === 'phone_order')> {{ __('loop.phone_order') }}
                        </label>
                    </div>
                </details>

                <template x-teleport="body">
                    <div
                        x-show="scanning"
                        x-cloak
                        class="fixed inset-0 z-[90] flex flex-col bg-ink"
                        @keydown.escape.window="close()"
                    >
                        <div class="flex items-center justify-between px-4 py-4">
                            <div class="flex items-center gap-2">
                                <x-loop-logo class="h-8 w-8" />
                                <span class="font-display text-lg font-semibold text-white">Loop</span>
                            </div>
                            <button type="button" class="rounded-xl bg-white/10 px-3 py-2 text-sm font-semibold text-white" @click="close()">{{ __('loop.close') }}</button>
                        </div>
                        <div class="relative mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-6 pb-10">
                            <p class="mb-4 text-center text-sm text-white/70">{{ __('loop.scan_member_qr_hint') }}</p>
                            <div class="relative aspect-square w-full max-w-sm overflow-hidden rounded-[1.75rem] ring-2 ring-lime/60">
                                <video x-ref="video" class="h-full w-full object-cover" playsinline muted></video>
                                <div class="pointer-events-none absolute inset-8 rounded-2xl border-2 border-lime/80"></div>
                            </div>
                            <p class="mt-4 text-center text-xs text-white/50" x-text="status"></p>
                            <p x-show="error" class="mt-2 text-center text-sm text-coral" x-text="error"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
        <button class="loop-btn w-full">{{ __('loop.continue') }}</button>
    </form>
    @endif

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
