<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.payments') }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold">{{ __('loop.waiting_for_payment') }}</h1>
            <p class="mt-1 text-ink-muted">{{ __('loop.waiting_for_payment_blurb') }}</p>
        </div>
    </x-slot>

    <div
        class="mx-auto max-w-lg loop-glass p-8 text-center"
        x-data="{
            status: @js($payment->status),
            paid: @js($payment->isPaid()),
            poll() {
                if (this.paid) return;
                fetch(@js(route('payments.status', $payment)))
                    .then(r => r.json())
                    .then(data => {
                        this.status = data.status;
                        this.paid = data.paid;
                        if (data.redirect) window.location = data.redirect;
                    })
                    .catch(() => {});
            }
        }"
        x-init="setInterval(() => poll(), 3000)"
    >
        <div class="mx-auto mb-6 h-14 w-14 animate-spin rounded-full border-4 border-violet/20 border-t-violet" x-show="!paid"></div>
        <p class="font-display text-2xl font-semibold" x-text="paid ? @js(__('loop.payment_success')) : @js(__('loop.approve_on_phone'))"></p>
        <p class="mt-3 text-sm text-ink-muted">{{ __('loop.payment_amount_line', ['amount' => number_format($payment->amount), 'currency' => $payment->currency]) }}</p>
        <p class="mt-1 text-sm text-ink-muted">{{ $payment->phone }}</p>
        <p class="mt-4 text-xs uppercase tracking-wide text-ink-muted">{{ __('loop.status') }}: <span x-text="status"></span></p>

        @if (str_starts_with((string) $payment->provider_ref, 'STUB-') && ! $payment->isPaid())
            <form method="POST" action="{{ route('payments.stub-confirm', $payment) }}" class="mt-6">
                @csrf
                <button class="loop-btn-mint">{{ __('loop.stub_confirm_payment') }}</button>
            </form>
            <p class="mt-2 text-xs text-ink-muted">{{ __('loop.stub_confirm_help') }}</p>
        @endif
    </div>
</x-app-layout>
