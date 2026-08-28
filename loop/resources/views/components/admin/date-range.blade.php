@props([
    'period',
    'tab' => 'overview',
])

@php
    $presets = [
        '7d' => __('loop.report_range_7d'),
        '14d' => __('loop.report_range_14d'),
        'month' => __('loop.report_range_month'),
        'all' => __('loop.report_range_all'),
        'custom' => __('loop.report_range_custom'),
    ];
@endphp

<div class="admin-range">
    <div>
        <p class="loop-label !mb-2">{{ __('loop.report_dates') }}</p>
        <div class="admin-range__presets">
            @foreach ($presets as $key => $label)
                <a
                    href="{{ route('admin.reports.index', $period->query(['tab' => $tab, 'range' => $key, 'from' => $key === 'custom' ? $period->from?->toDateString() : null, 'to' => $key === 'custom' ? $period->to?->toDateString() : null])) }}"
                    class="admin-range__chip {{ $period->preset === $key ? 'is-active' : '' }}"
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>
    @if ($period->preset === 'custom')
        <form method="GET" action="{{ route('admin.reports.index') }}" class="admin-range__dates">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="range" value="custom">
            <div>
                <label class="loop-label" for="report-from">{{ __('loop.from') }}</label>
                <input id="report-from" type="date" name="from" value="{{ $period->from?->toDateString() }}" class="loop-input !mt-1">
            </div>
            <div>
                <label class="loop-label" for="report-to">{{ __('loop.to') }}</label>
                <input id="report-to" type="date" name="to" value="{{ $period->to?->toDateString() }}" class="loop-input !mt-1">
            </div>
            <button class="admin-btn !py-2.5">{{ __('loop.apply') }}</button>
        </form>
    @endif
</div>
