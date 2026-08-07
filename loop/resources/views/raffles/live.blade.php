<x-app-layout>
    <x-slot name="header">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-mint-deep">{{ __('loop.live_raffle') }}</p>
            <h1 class="mt-2 font-display text-4xl font-semibold">{{ $raffle->name }}</h1>
            <p class="mt-2 text-ink-muted">{{ $raffle->prize_name }} · {{ $remaining }} {{ __('loop.slots_left') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-lg" x-data="{
        spinning: false,
        names: @js($raffle->winners->map(fn ($w) => $w->displayFirstName().' '.$w->displayLastBlurred())->values()),
        flash: ['Amani ••••','Neema •••••','Juma ••••','Asha •••••','Baraka •••••','Fatuma •••••','Daniel •••••','Grace •••••'],
        shown: '',
        timer: null,
        startSpin(e) {
            this.spinning = true;
            let i = 0;
            this.timer = setInterval(() => {
                this.shown = this.flash[i % this.flash.length];
                i++;
            }, 90);
        }
    }">
        <div class="relative overflow-hidden rounded-[2rem] bg-ink p-8 text-center text-white shadow-[0_40px_100px_rgba(11,31,42,0.35)]">
            <div class="pointer-events-none absolute -left-10 -top-10 h-40 w-40 rounded-full bg-mint/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-10 -right-10 h-40 w-40 rounded-full bg-coral/20 blur-3xl"></div>
            <p class="relative text-xs font-semibold uppercase tracking-[0.16em] text-mint">{{ __('loop.drawing') }}</p>
            <div class="relative mt-8 min-h-[7rem]">
                @if (session('drawn_winner_id'))
                    @php $latest = $raffle->winners->firstWhere('id', session('drawn_winner_id')) ?? $raffle->winners->last(); @endphp
                    @if ($latest)
                        <p class="animate-fade-up font-display text-5xl font-semibold" x-show="!spinning">
                            {{ $latest->displayFirstName() }}
                            <span class="text-white/40">{{ $latest->displayLastBlurred() }}</span>
                        </p>
                        <p class="mt-3 text-sm text-mint" x-show="!spinning">{{ __('loop.you_won') }} · {{ $raffle->prize_name }}</p>
                    @endif
                @else
                    <p class="font-display text-3xl font-semibold text-white/40" x-show="!spinning && !shown">{{ __('loop.ready_to_draw') }}</p>
                @endif
                <p class="font-display text-4xl font-semibold tracking-tight" x-show="spinning || shown" x-text="shown" x-cloak></p>
            </div>

            @if ($remaining > 0)
                <form method="POST" action="{{ route('raffles.draw', $raffle) }}" class="relative mt-8" @submit="startSpin">
                    @csrf
                    <button class="loop-btn-mint w-full text-lg" :disabled="spinning">
                        <span x-show="!spinning">{{ session('drawn_winner_id') ? __('loop.draw_next') : __('loop.draw_winner') }}</span>
                        <span x-show="spinning" x-cloak>{{ __('loop.spinning') }}…</span>
                    </button>
                </form>
            @else
                <a href="{{ route('raffles.show', $raffle) }}" class="loop-btn-mint relative mt-8 inline-flex w-full justify-center">{{ __('loop.view_raffle') }}</a>
            @endif
        </div>

        <div class="mt-8 space-y-2">
            @foreach ($raffle->winners as $winner)
                <div class="flex items-center justify-between rounded-2xl border border-ink/8 bg-white px-4 py-3">
                    <div>
                        <p class="font-semibold">{{ $winner->displayFirstName() }} <span class="text-ink-muted">{{ $winner->displayLastBlurred() }}</span></p>
                        <p class="text-xs text-ink-muted">{{ $winner->customer->full_phone }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="tel:{{ preg_replace('/\s+/', '', $winner->customer->full_phone) }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.call_winner') }}</a>
                        <button type="button" class="text-sm font-semibold text-ink-muted" onclick="navigator.clipboard.writeText(@js($winner->customer->full_phone)); this.textContent=@js(__('loop.copied'))">{{ __('loop.copy_number') }}</button>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-6 text-center text-xs text-ink-muted">{{ __('loop.live_raffle_privacy') }}</p>
    </div>
</x-app-layout>
