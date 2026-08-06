<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-3xl font-semibold">{{ __('loop.customers') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.customers_blurb', ['count' => $memberCount]) }}</p>
        </div>
    </x-slot>

    <div class="space-y-3">
        @forelse ($customers as $customer)
            <a href="{{ route('customers.show', $customer) }}" class="flex items-center justify-between gap-4 rounded-[1.5rem] border border-ink/8 bg-white/90 px-5 py-4 transition hover:-translate-y-0.5 hover:bg-white">
                <div class="min-w-0">
                    <p class="font-semibold">{{ $customer->name }}</p>
                    <p class="mt-1 text-xs text-ink-muted">{{ $customer->full_phone }} · {{ __('loop.joined') }} {{ optional($customer->first_joined_at)->format('d M Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="font-display text-2xl font-semibold">{{ number_format((int) $customer->points_balance) }}</p>
                    <p class="text-xs text-ink-muted">pts</p>
                </div>
            </a>
        @empty
            <div class="loop-panel p-8 text-center text-sm text-ink-muted">{{ __('loop.no_customers_yet') }}</div>
        @endforelse
    </div>

    <div class="mt-8">{{ $customers->links() }}</div>
</x-app-layout>
