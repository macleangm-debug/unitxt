<x-app-layout>
    @php
        $totalPoints = $grouped->flatten()->sum('points_balance');
        $walletCount = $grouped->flatten()->count();
    @endphp

    <section class="loop-wallet mb-8 px-5 py-7 sm:px-8 sm:py-9">
        <div class="loop-orb loop-orb--a"></div>
        <div class="loop-orb loop-orb--b"></div>
        <div class="loop-orb loop-orb--c"></div>
        <div class="relative">
            <div class="flex items-end justify-between gap-4">
                <div class="flex min-w-0 flex-1 flex-col justify-between self-stretch">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-lime/80">Loop</p>
                        <h1 class="mt-2 font-display text-[clamp(1.85rem,7vw,2.65rem)] font-semibold leading-tight tracking-tight text-white">
                            {{ __('loop.my_wallets') }}
                        </h1>
                        <p class="mt-2 max-w-xs text-sm leading-relaxed text-white/55">{{ __('loop.my_wallets_blurb') }}</p>
                    </div>
                    <div class="mt-6">
                        <div x-data="loopCountUp({{ (int) $totalPoints }})">
                            <p class="font-display text-[clamp(3.25rem,13vw,5rem)] font-semibold leading-none tracking-tight text-lime" x-text="formatted()">{{ number_format($totalPoints) }}</p>
                        </div>
                        <p class="mt-2 text-sm font-medium uppercase tracking-[0.16em] text-white/55">{{ __('loop.pts') }}</p>
                        <p class="mt-2 text-sm text-white/65">{{ __('loop.across_shops', ['count' => $walletCount]) }}</p>
                    </div>
                </div>
                <x-wallet-qr :size="120" class="shrink-0" />
            </div>
        </div>
    </section>

    @forelse ($grouped as $sector => $items)
        <section class="mb-10">
            <x-section-heading
                :eyebrow="__('loop.sector')"
                :title="\App\Support\Sectors::label($sector)"
                class="mb-5"
            />
            <div class="loop-carousel items-stretch" x-data="loopParallaxCarousel()">
                @foreach ($items as $membership)
                    <x-wallet-membership-card
                        :membership="$membership"
                        :carousel="true"
                        data-loop-card
                    />
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-[1.5rem] border border-dashed border-ink/15 bg-white/60 px-6 py-12 text-center">
            <p class="font-display text-xl font-semibold">{{ __('loop.no_wallets_yet') }}</p>
            <a href="{{ route('discover') }}" class="loop-btn mt-5 inline-flex">{{ __('loop.explore') }}</a>
        </div>
    @endforelse
</x-app-layout>
