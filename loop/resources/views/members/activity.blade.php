<x-app-layout>
    <x-slot name="header">
        <h1 class="loop-page-title font-display text-3xl font-semibold">{{ __('loop.activity') }}</h1>
        <p class="mt-1 text-ink-muted">{{ __('loop.activity_blurb') }}</p>
    </x-slot>

    <div class="space-y-2">
        @forelse ($recent as $row)
            <div class="flex items-center justify-between rounded-[1.25rem] border border-ink/8 bg-white/80 px-4 py-3">
                <p class="text-sm font-semibold">{{ $row->shop_name }}</p>
                <p class="font-display text-sm font-semibold {{ $row->points >= 0 ? 'text-mint-deep' : 'text-coral' }}">
                    {{ $row->points >= 0 ? '+' : '' }}{{ number_format((int) $row->points) }}
                </p>
            </div>
        @empty
            <div class="rounded-[1.5rem] border border-dashed border-ink/15 bg-chalk/40 px-5 py-8 text-center">
                <p class="font-display text-lg font-semibold">{{ __('loop.no_history_yet') }}</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
