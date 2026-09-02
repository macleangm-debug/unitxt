<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="$checkout['back']" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.payments') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.pay_now') }}</h1>
                <p class="mt-1 max-w-xl text-ink-muted">{{ $checkout['body'] }}</p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-lg">
        <div class="loop-panel p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ $checkout['title'] }}</p>
            <p class="mt-3 font-display text-4xl font-semibold">{{ $checkout['currency'] }} {{ number_format($checkout['amount']) }}</p>
            @if (! empty($checkout['save']))
                <p class="mt-1 text-sm font-semibold text-mint-deep">{{ __('loop.interval_save_money', ['currency' => $checkout['currency'], 'amount' => number_format($checkout['save'])]) }}</p>
            @endif
            @if (! empty($checkout['months']))
                <p class="mt-1 text-sm text-ink-muted">{{ trans_choice('loop.interval_month_count', $checkout['months'], ['count' => $checkout['months']]) }}</p>
            @endif
            <p class="mt-1 text-sm text-ink-muted">{{ __('loop.approve_on_phone') }}</p>

            <form method="POST" action="{{ route('payments.checkout') }}" class="mt-6 space-y-4" data-loop-quiet>
                @csrf
                <input type="hidden" name="purpose" value="{{ $checkout['purpose'] }}">
                @foreach ($checkout['fields'] as $name => $value)
                    @if ($value !== '' && $value !== null)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div>
                    <label class="loop-label">{{ __('loop.pay_with_phone') }}</label>
                    <div class="flex gap-2">
                        <span class="inline-flex items-center rounded-2xl border border-ink/10 bg-chalk px-3 text-sm font-semibold">{{ $dial }}</span>
                        <input name="phone" value="{{ $phone }}" class="loop-input !mt-0" placeholder="7XXXXXXXX" required>
                    </div>
                    <p class="mt-1 text-xs text-ink-muted">{{ __('loop.pay_phone_prefilled_help') }}</p>
                </div>

                <button class="loop-btn w-full">{{ __('loop.pay_now') }}</button>
                <a href="{{ $checkout['back'] }}" class="loop-btn-ghost w-full">{{ __('loop.cancel') }}</a>
            </form>
        </div>
    </div>
</x-app-layout>
