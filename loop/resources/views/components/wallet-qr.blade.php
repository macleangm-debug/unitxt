@props([
    'size' => 132,
    'expandable' => true,
])

@php
    $qrUrl = route('customer.wallet-qr');
    $frame = (int) $size + 20;
@endphp

<div
    {{ $attributes->merge(['class' => 'loop-wallet-qr relative shrink-0']) }}
    @if ($expandable)
        x-data="loopQrExpand()"
    @endif
>
    <button
        type="button"
        @if ($expandable)
            @click="open($refs.thumb)"
            x-ref="thumb"
        @endif
        class="group relative block rounded-[1.35rem] p-[2px] shadow-[0_12px_40px_rgba(0,0,0,0.35)] outline-none transition focus-visible:ring-2 focus-visible:ring-lime/80"
        style="background: linear-gradient(135deg, #C8FF3D 0%, #5B2EFF 55%, #C8FF3D 100%); width: {{ $frame }}px;"
        @if ($expandable)
            aria-label="{{ __('loop.wallet_qr_expand') }}"
        @endif
    >
        <span class="block overflow-hidden rounded-[1.25rem] bg-white p-4">
            <img
                src="{{ $qrUrl }}"
                alt="{{ __('loop.wallet_qr_title') }}"
                class="mx-auto block"
                style="width: {{ (int) $size }}px; height: {{ (int) $size }}px;"
                width="{{ (int) $size }}"
                height="{{ (int) $size }}"
                draggable="false"
            >
        </span>
    </button>

    @if ($expandable)
        <template x-teleport="body">
            <div
                x-show="visible"
                x-cloak
                class="fixed inset-0 z-[95] flex items-center justify-center p-6"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('loop.wallet_qr_title') }}"
            >
                <div
                    class="absolute inset-0 bg-ink/75 backdrop-blur-md loop-fade"
                    :class="expanded ? 'opacity-100' : 'opacity-0'"
                    @click="close()"
                ></div>
                <div
                    class="relative z-10 will-change-transform"
                    :style="frameStyle"
                    @click.stop
                >
                    <div
                        class="rounded-[1.75rem] p-[3px] shadow-[0_28px_80px_rgba(0,0,0,0.45)]"
                        style="background: linear-gradient(135deg, #C8FF3D 0%, #5B2EFF 55%, #C8FF3D 100%);"
                    >
                        <div class="rounded-[1.6rem] bg-white p-4 sm:p-5">
                            <img
                                src="{{ $qrUrl }}"
                                alt="{{ __('loop.wallet_qr_title') }}"
                                class="mx-auto block h-auto w-full"
                                width="280"
                                height="280"
                                draggable="false"
                            >
                        </div>
                    </div>
                    <button
                        type="button"
                        class="mt-5 w-full text-center text-sm font-semibold text-white/80 transition hover:text-white"
                        @click="close()"
                        x-show="expanded"
                        x-transition.opacity
                    >
                        {{ __('loop.close') }}
                    </button>
                </div>
            </div>
        </template>
    @endif
</div>
