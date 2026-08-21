@props([
    'business',
    'wantedBack' => false,
])

@if ($wantedBack)
    <p class="text-sm font-semibold text-lime/90">{{ __('loop.want_loop_back_sent') }}</p>
@else
    <form method="POST" action="{{ route('memberships.want-back', $business) }}" class="mt-3">
        @csrf
        <button class="loop-btn-lime !py-2">{{ __('loop.want_loop_back') }}</button>
        <p class="mt-2 text-xs text-white/55">{{ __('loop.want_loop_back_hint', ['name' => $business->name]) }}</p>
    </form>
@endif
