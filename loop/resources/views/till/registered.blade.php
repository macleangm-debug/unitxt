<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">{{ __('loop.sale') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.customer_registered_title') }}</h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-md text-center">
        <a href="{{ route('till.ticket') }}" class="loop-btn w-full">{{ __('loop.continue_to_sale') }}</a>
    </div>
</x-app-layout>
