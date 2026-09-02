@props([
    'business',
    'momentum',
    'demand' => true,
])

<ul class="mt-4 space-y-2 text-sm">
    <li class="font-semibold">{{ __('loop.loop_paused_members', ['count' => number_format($momentum['members'])]) }}</li>
    @if ($momentum['close_to_reward'] > 0)
        <li>{{ __('loop.loop_paused_close', ['count' => number_format($momentum['close_to_reward'])]) }}</li>
    @endif
    @if ($momentum['rewards_ready'] > 0)
        <li>{{ __('loop.loop_paused_ready', ['count' => number_format($momentum['rewards_ready'])]) }}</li>
    @endif
    @if ($momentum['active_campaigns'] > 0)
        <li>{{ __('loop.loop_paused_campaigns', ['count' => number_format($momentum['active_campaigns'])]) }}</li>
    @endif
    <li>{{ __('loop.loop_paused_offers') }}</li>
    <li>{{ __('loop.loop_paused_discover') }}</li>
    @if ($business->hotline)
        <li>{{ __('loop.loop_paused_hotline') }}</li>
    @endif
    @if ($momentum['active_raffles'] > 0)
        <li>{{ __('loop.loop_paused_raffles', ['count' => number_format($momentum['active_raffles'])]) }}</li>
    @endif
    @if ($demand && $momentum['loop_back'] > 0)
        <li class="font-semibold text-violet">{{ trans_choice('loop.loop_paused_demand_title', $momentum['loop_back'], ['count' => $momentum['loop_back']]) }}</li>
    @endif
</ul>
