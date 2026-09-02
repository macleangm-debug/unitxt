<x-guest-layout
    :aside-title="__('loop.customer_aside_title')"
    :aside-body="__('loop.customer_aside_body')"
    :aside-stamp="__('loop.customer_stamp')"
    :aside-point1="__('loop.customer_aside_1')"
    :aside-point2="__('loop.customer_aside_2')"
    :aside-point3="__('loop.customer_aside_3')"
>
    <form method="POST" action="{{ route('customer.pin.verify') }}" class="space-y-4" autocomplete="off">
        @csrf
        <div>
            <h1 class="font-display text-2xl font-semibold">{{ __('loop.enter_pin') }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $auth['country_code'] }} {{ $auth['phone'] }}</p>
        </div>
        <div>
            <label class="loop-label">{{ __('loop.pin') }}</label>
            <input name="pin" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" class="loop-input loop-secret text-center text-2xl tracking-[0.4em]" required autofocus autocomplete="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true">
        </div>
        <button class="loop-btn w-full">{{ __('loop.continue') }}</button>
        <a href="{{ route('customer.login') }}" class="block text-center text-sm font-semibold text-ink-muted hover:text-ink">{{ __('loop.use_different_phone') }}</a>
    </form>
</x-guest-layout>
