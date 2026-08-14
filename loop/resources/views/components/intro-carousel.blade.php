@props([
    'audience' => 'customer', // customer|business|affiliate
])

@php
    $slides = match ($audience) {
        'business' => [
            ['title' => __('loop.intro_business_1_title'), 'body' => __('loop.intro_business_1_body')],
            ['title' => __('loop.intro_business_2_title'), 'body' => __('loop.intro_business_2_body')],
            ['title' => __('loop.intro_business_3_title'), 'body' => __('loop.intro_business_3_body')],
        ],
        'affiliate' => [
            ['title' => __('loop.intro_affiliate_1_title'), 'body' => __('loop.intro_affiliate_1_body')],
            ['title' => __('loop.intro_affiliate_2_title'), 'body' => __('loop.intro_affiliate_2_body')],
            ['title' => __('loop.intro_affiliate_3_title'), 'body' => __('loop.intro_affiliate_3_body')],
        ],
        default => [
            ['title' => __('loop.intro_customer_1_title'), 'body' => __('loop.intro_customer_1_body')],
            ['title' => __('loop.intro_customer_2_title'), 'body' => __('loop.intro_customer_2_body')],
            ['title' => __('loop.intro_customer_3_title'), 'body' => __('loop.intro_customer_3_body')],
        ],
    };
@endphp

<div
    x-data="{
        i: 0,
        total: {{ count($slides) }},
        touchX: null,
        next() { this.i = Math.min(this.total - 1, this.i + 1); },
        prev() { this.i = Math.max(0, this.i - 1); },
        onTouchStart(e) { this.touchX = e.changedTouches[0].screenX; },
        onTouchEnd(e) {
            if (this.touchX === null) return;
            const dx = e.changedTouches[0].screenX - this.touchX;
            if (dx < -40) this.next();
            if (dx > 40) this.prev();
            this.touchX = null;
        }
    }"
    class="mb-8 overflow-hidden rounded-[1.75rem] border border-ink/10 bg-gradient-to-br from-violet-soft/50 via-white to-mint-soft/40 p-6 shadow-[0_20px_60px_rgba(11,31,42,0.08)] sm:p-8"
    @touchstart.passive="onTouchStart"
    @touchend.passive="onTouchEnd"
>
    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-mint-deep">Loop</p>
    <div class="mt-4 min-h-[7.5rem]">
        @foreach ($slides as $idx => $slide)
            <div x-show="i === {{ $idx }}" x-cloak x-transition.opacity.duration.200ms>
                <h2 class="font-display text-2xl font-semibold sm:text-3xl">{{ $slide['title'] }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-ink-muted sm:text-base">{{ $slide['body'] }}</p>
            </div>
        @endforeach
    </div>
    <div class="mt-6 flex items-center justify-between gap-3">
        <div class="flex gap-1.5">
            @foreach ($slides as $idx => $slide)
                <button type="button" class="h-1.5 rounded-full transition-all" :class="i === {{ $idx }} ? 'w-6 bg-violet' : 'w-1.5 bg-ink/15'" @click="i = {{ $idx }}" aria-label="Slide {{ $idx + 1 }}"></button>
            @endforeach
        </div>
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('preference.intro') }}">
                @csrf
                <button type="submit" class="text-sm font-semibold text-ink-muted underline">{{ __('loop.skip') }}</button>
            </form>
            <button
                type="button"
                class="loop-btn-mint !py-2"
                x-show="i < total - 1"
                @click="next()"
            >{{ __('loop.next') }}</button>
            <form method="POST" action="{{ route('preference.intro') }}" x-show="i === total - 1" x-cloak>
                @csrf
                <button class="loop-btn-mint !py-2">{{ __('loop.got_it') }}</button>
            </form>
        </div>
    </div>
</div>
