@props(['raffle', 'winner', 'calling' => false])

@php
    $tel = preg_replace('/\s+/', '', (string) $winner->customer?->full_phone);
@endphp
<a
    href="tel:{{ $tel }}"
    class="loop-btn-mint !py-2 !text-sm"
    @click="
        @if ($calling) signalCalling(); @endif
        status = 'contacted';
        fetch(@js(route('raffles.contact', [$raffle, $winner])), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
            },
        })
    "
>{{ __('loop.call_winner') }}</a>
