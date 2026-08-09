@php
    $confirm = $confirm ?? session('confirm');
@endphp

@if ($confirm)
    <div
        x-data="{ open: true }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center px-4"
        @keydown.escape.window="open=false"
    >
        <div class="absolute inset-0 bg-ink/60 backdrop-blur-sm" @click="open=false"></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-ink/10 bg-white p-8 text-center shadow-[0_40px_100px_rgba(17,17,20,0.35)] sm:p-10">
            @if (!empty($confirm['celebrate']))
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    @foreach (range(1,36) as $i)
                        <span class="absolute opacity-90"
                              style="left: {{ rand(2,96) }}%; top: -12%; width: {{ rand(6,12) }}px; height: {{ rand(8,16) }}px; border-radius: {{ $i % 3 === 0 ? '999px' : '2px' }}; background: {{ ['#2E7D32','#5B2EFF','#111114','#C8FF3D','#FF4F70'][$i % 5] }}; animation: loop-confetti {{ 1.6 + ($i % 5) * 0.18 }}s ease-in {{ $i * 0.04 }}s infinite;"></span>
                    @endforeach
                </div>
                <style>
                    @keyframes loop-confetti {
                        0% { transform: translate3d(0,-10%,0) rotate(0deg); opacity: 0; }
                        12% { opacity: 1; }
                        100% { transform: translate3d({{ rand(-40,40) }}px, 120vh, 0) rotate({{ rand(180,720) }}deg); opacity: 0; }
                    }
                </style>
            @endif
            <div class="relative">
                @if (!empty($confirm['celebrate']))
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-mint-deep text-3xl font-bold text-white shadow-[0_12px_40px_rgba(27,94,32,0.35)]">✓</div>
                @else
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-coral/15 text-3xl font-bold text-coral ring-1 ring-coral/25">!</div>
                @endif
                <p class="mt-6 font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">{{ $confirm['title'] }}</p>
                <p class="mt-3 text-base font-medium leading-relaxed text-ink-muted sm:text-lg">{{ $confirm['body'] }}</p>
                <a href="{{ $confirm['url'] }}" class="loop-btn mt-8 inline-flex w-full justify-center text-base" @click="open=false">{{ $confirm['cta'] }}</a>
                <button type="button" class="mt-4 text-sm font-semibold text-ink-muted hover:text-ink" @click="open=false">{{ __('loop.done') }}</button>
            </div>
        </div>
    </div>
@endif
