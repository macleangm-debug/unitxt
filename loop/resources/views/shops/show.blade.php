<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.shops') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $shop->name }}</h1>
                <p class="mt-1 text-ink-muted">{{ $shop->city }} · {{ $shop->code }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-settings-back />
                <a href="{{ route('shops.edit', $shop) }}" class="loop-btn-mint !py-2.5">{{ __('loop.edit') }}</a>
            </div>
        </div>
    </x-slot>

    <section class="mx-auto max-w-xl rounded-[2rem] border border-ink/8 bg-white/90 p-7">
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
</x-app-layout>
