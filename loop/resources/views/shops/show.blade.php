<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.shops') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $shop->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $shop->city }} · {{ $shop->code }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('shops.index') }}" class="loop-btn-ghost !py-2">{{ __('loop.back') }}</a>
                <a href="{{ route('shops.edit', $shop) }}" class="loop-btn-mint !py-2">{{ __('loop.edit') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-[2rem] border border-ink/8 bg-white/90 p-7">
            <div class="flex items-center gap-4">
                <x-shop-logo :shop="$shop" class="h-20 w-20 rounded-[1.5rem]" />
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-muted">{{ __('loop.business_logo') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('loop.shared_logo_hint') }}</p>
                    <a href="{{ route('business.edit') }}" class="mt-2 inline-flex text-sm font-semibold text-mint-deep">{{ __('loop.edit_business_logo') }} →</a>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-ink/8 bg-white/90 p-7">
            <h2 class="font-display text-xl font-semibold">{{ __('loop.branch_details') }}</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex justify-between gap-4 border-b border-ink/5 pb-3">
                    <dt class="text-ink-muted">{{ __('loop.status') }}</dt>
                    <dd class="font-semibold">{{ $shop->is_active ? __('loop.active') : __('loop.inactive') }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-ink/5 pb-3">
                    <dt class="text-ink-muted">{{ __('loop.city') }}</dt>
                    <dd class="font-semibold">{{ $shop->city }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-ink/5 pb-3">
                    <dt class="text-ink-muted">{{ __('loop.address') }}</dt>
                    <dd class="max-w-[60%] text-right font-semibold">{{ $shop->address ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-ink-muted">{{ __('loop.phone') }}</dt>
                    <dd class="font-semibold">{{ $shop->phone ?: '—' }}</dd>
                </div>
            </dl>
        </section>
    </div>
</x-app-layout>
