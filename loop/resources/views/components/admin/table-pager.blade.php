@props([
    'paginator' => null,
])

@if ($paginator)
    @php
        $from = $paginator->firstItem();
        $to = $paginator->lastItem();
        $total = $paginator->total();
        $current = (int) $paginator->perPage();
    @endphp
    <div class="admin-pager">
        <p class="admin-pager__meta">
            {{ __('loop.admin_showing_rows', ['from' => $from ?: 0, 'to' => $to ?: 0, 'total' => $total]) }}
        </p>
        <form method="GET" class="admin-pager__size">
            @foreach (request()->except('page', 'per_page') as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $inner)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $inner }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label>
                <span>{{ __('loop.admin_rows_per_page') }}</span>
                <select name="per_page" onchange="this.form.submit()">
                    @foreach (\App\Support\AdminPagination::OPTIONS as $n)
                        <option value="{{ $n }}" @selected($current === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </label>
        </form>
        <div class="admin-pager__links">{{ $paginator->onEachSide(1)->links() }}</div>
    </div>
@endif
