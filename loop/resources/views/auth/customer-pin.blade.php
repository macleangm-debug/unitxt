<x-guest-layout>
    <x-slot name="asideTitle">{{ __('loop.customer_aside_title') }}</x-slot>
    <x-slot name="asideBody">{{ __('loop.customer_aside_body') }}</x-slot>
    <x-slot name="asideStamp">{{ __('loop.customer_stamp') }}</x-slot>
    <x-slot name="asidePoint1">{{ __('loop.customer_aside_1') }}</x-slot>
    <x-slot name="asidePoint2">{{ __('loop.customer_aside_2') }}</x-slot>
    <x-slot name="asidePoint3">{{ __('loop.customer_aside_3') }}</x-slot>

    @if ($errors->has('pin'))
        <div class="space-y-5 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-coral/15 text-coral">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
            </div>
            <div>
                <h1 class="font-display text-2xl font-semibold">{{ __('loop.pin_wrong_title') }}</h1>
                <p class="mt-2 text-sm text-ink-muted">{{ __('loop.pin_wrong_body') }}</p>
                <p class="mt-1 text-xs text-ink-muted">{{ $auth['country_code'] }} {{ $auth['phone'] }}</p>
            </div>
            <a href="{{ route('customer.pin') }}" class="loop-btn-mint inline-flex w-full justify-center">{{ __('loop.try_again') }}</a>
            <a href="{{ route('customer.login') }}" class="block text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.use_different_phone') }}</a>
        </div>
    @else
        <form method="POST" action="{{ route('customer.pin.verify') }}" class="space-y-4">
            @csrf
            <div>
                <h1 class="font-display text-2xl font-semibold">{{ __('loop.enter_pin') }}</h1>
                <p class="mt-1 text-sm text-ink-muted">{{ $auth['country_code'] }} {{ $auth['phone'] }}</p>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.pin') }}</label>
                <input name="pin" inputmode="numeric" maxlength="6" class="loop-input text-center text-2xl tracking-[0.4em]" required autofocus>
            </div>
            <button class="loop-btn-mint w-full">{{ __('loop.continue') }}</button>
        </form>
    @endif
</x-guest-layout>
