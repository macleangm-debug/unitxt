<x-app-layout>
    <x-slot name="header">
        <div class="loop-wallet loop-wallet--liquid mb-2 px-5 py-6 sm:px-7 sm:py-7" x-data="loopLivingWallet()">
            <div class="loop-orb loop-orb--a loop-orb--enter"></div>
            <div class="loop-orb loop-orb--b loop-orb--enter"></div>
            <div class="relative">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-lime">Loop</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('loop.sale') }}</h1>
                <p class="mt-1 text-sm text-white/60">{{ __('loop.sale_blurb_short') }}</p>
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

    <form method="POST" action="{{ route('till.lookup') }}" class="loop-panel mx-auto max-w-xl space-y-4 p-6 animate-fade-up {{ ! empty($tillLocked) ? 'pointer-events-none opacity-50' : '' }}">
        @csrf
        <div>
            <label class="loop-label">{{ __('loop.shop') }}</label>
            <select name="shop_id" class="loop-input" required>
                @foreach ($shops as $shop)
                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.channel') }}</label>
            <div class="mt-2 grid grid-cols-2 gap-3">
                <label class="rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft">
                    <input type="radio" name="channel" value="in_store" class="sr-only" checked> {{ __('loop.in_store') }}
                </label>
                <label class="rounded-xl border border-ink/10 bg-chalk px-4 py-3 text-sm has-[:checked]:border-mint has-[:checked]:bg-mint-soft">
                    <input type="radio" name="channel" value="phone_order" class="sr-only"> {{ __('loop.phone_order') }}
                </label>
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
            <div>
                <label class="loop-label">{{ __('loop.country_prefix') }}</label>
                <select name="country_code" class="loop-input">
                    @foreach ($countries as $meta)
                        <option value="{{ $meta['dial'] }}" @selected($meta['dial'] === '+255')>{{ $meta['flag'] }} {{ $meta['dial'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.customer_phone') }}</label>
                <input name="phone" class="loop-input text-lg" placeholder="+255 712 345 678" required autofocus>
            </div>
        </div>
        <button class="loop-btn-mint w-full">{{ __('loop.look_up') }}</button>
    </form>

    @if (! empty($showRecent))
        <section class="mx-auto mt-10 max-w-xl">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">{{ __('loop.recent_till') }}</h2>
                <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-mint-deep">{{ __('loop.view_all') }} →</a>
            </div>
            <div class="space-y-3">
                @forelse ($recent as $visit)
                    <div class="loop-panel flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div>
                            <p class="font-semibold">{{ $visit->customer->name }} · {{ $visit->shop->name }}</p>
                            <p class="text-sm text-ink-muted">
                                {{ $business->currency }} {{ number_format($visit->amount_spent, 0) }}
                                · {{ $visit->channel === 'phone_order' ? __('loop.phone_order') : __('loop.in_store') }}
                                · {{ $visit->created_at->format('d M Y · H:i') }}
                            </p>
                        </div>
                        <span class="rounded-lg bg-mint-soft px-2.5 py-1 text-sm font-semibold">+{{ $visit->points_earned }} {{ __('loop.pts') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">{{ __('loop.no_sales_yet') }}</p>
                @endforelse
            </div>
        </section>
    @endif
</x-app-layout>
