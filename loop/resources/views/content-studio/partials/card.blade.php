<div
    class="loop-studio-card relative aspect-square w-full overflow-hidden rounded-[2rem] shadow-[0_30px_80px_rgba(11,31,42,0.18)]"
    :class="cardToneClass()"
>
    <template x-if="look === 'photo' && photoUrl">
        <div class="absolute inset-0">
            <img :src="photoUrl" alt="" class="loop-studio-photo h-full w-full" :style="'object-position: 50% ' + photoY + '%'">
            <div class="loop-studio-grade absolute inset-0"></div>
        </div>
    </template>
    <div class="relative flex h-full flex-col justify-between p-7 sm:p-8" :class="look === 'photo' && photoUrl ? 'text-white' : ''">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                @if ($business->logoUrl())
                    <img src="{{ $business->logoUrl() }}" alt="" class="h-12 w-12 rounded-2xl object-cover ring-2 ring-white/30">
                @else
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 font-display text-xl font-semibold ring-2 ring-white/20">{{ mb_substr($business->name,0,1) }}</div>
                @endif
                <div class="min-w-0">
                    <p class="truncate font-display text-lg font-semibold leading-tight">{{ $business->name }}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-1.5 opacity-80">
                <x-loop-logo class="h-7 w-7" />
                <span class="text-[11px] font-semibold uppercase tracking-[0.14em]">Loop</span>
            </div>
        </div>

        <div class="py-6">
            <p class="font-display text-[1.85rem] font-semibold leading-[1.15] sm:text-4xl" x-text="headline()"></p>
            <p class="mt-3 text-sm font-medium opacity-80" x-show="supportLine()" x-text="supportLine()"></p>
        </div>

        <div>
            @if ($business->hotline)
                <p class="text-sm font-semibold tracking-wide opacity-90">{{ $business->hotline }}</p>
            @endif
        </div>
    </div>
</div>
