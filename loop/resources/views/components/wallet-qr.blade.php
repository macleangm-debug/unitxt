@props([
    'name',
    'phone',
    'size' => 132,
])

@php
    $qrUrl = route('customer.wallet-qr');
@endphp

<div {{ $attributes->merge(['class' => 'loop-wallet-qr relative shrink-0']) }}>
    <div class="relative rounded-[1.35rem] p-[2px] shadow-[0_12px_40px_rgba(0,0,0,0.35)]" style="background: linear-gradient(135deg, #C8FF3D 0%, #5B2EFF 55%, #C8FF3D 100%);">
        <div class="rounded-[1.25rem] bg-white px-3 pb-3 pt-2.5">
            <div class="mb-1.5 flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                    <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" aria-hidden="true">
                        <defs>
                            <linearGradient id="walletQrGrad" x1="8" y1="12" x2="56" y2="52" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#5B2EFF"/>
                                <stop offset="1" stop-color="#C8FF3D"/>
                            </linearGradient>
                        </defs>
                        <path d="M20 32c0-7.732 6.268-14 14-14h2c7.732 0 14 6.268 14 14s-6.268 14-14 14h-2c-7.732 0-14-6.268-14-14Z" stroke="url(#walletQrGrad)" stroke-width="5.5" stroke-linecap="round"/>
                        <circle cx="22" cy="32" r="3.2" fill="#5B2EFF"/>
                        <circle cx="42" cy="32" r="3.2" fill="#C8FF3D"/>
                    </svg>
                    <span class="font-display text-[11px] font-semibold tracking-tight text-ink">Loop</span>
                </div>
                <span class="rounded-md bg-violet-soft px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-[0.12em] text-violet">{{ __('loop.wallet_qr_badge') }}</span>
            </div>
            <div class="overflow-hidden rounded-xl bg-chalk/80 p-1.5 ring-1 ring-ink/5">
                <img
                    src="{{ $qrUrl }}"
                    alt="{{ __('loop.wallet_qr_title') }}"
                    class="mx-auto block"
                    style="width: {{ (int) $size }}px; height: {{ (int) $size }}px;"
                    width="{{ (int) $size }}"
                    height="{{ (int) $size }}"
                >
            </div>
            <p class="mt-2 truncate text-center text-[11px] font-semibold text-ink">{{ $name }}</p>
            <p class="truncate text-center font-mono text-[10px] text-ink-muted">{{ $phone }}</p>
        </div>
    </div>
</div>
