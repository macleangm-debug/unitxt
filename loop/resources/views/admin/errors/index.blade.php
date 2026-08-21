<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1>{{ __('loop.admin_errors') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('loop.admin_errors_blurb') }}</p>
            </div>
            <a href="{{ route('admin.errors.preview', ['kind' => '500']) }}" class="admin-btn-ghost !py-2">{{ __('loop.error_preview') }}</a>
        </div>
    </x-slot>

    <x-admin.empty-state :empty="$hits->isEmpty()" :title="__('loop.admin_errors_empty')">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>{{ __('loop.error_page') }}</th>
                        <th>{{ __('loop.error_response') }}</th>
                                <th>{{ __('loop.error_hits') }}</th>
                        <th>{{ __('loop.when') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hits as $hit)
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $hit->method }} {{ $hit->path }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">{{ $hit->route_name ?: '—' }}</p>
                            </td>
                            <td>
                                <p class="font-semibold">{{ $hit->status_code }} · {{ class_basename($hit->exception_class) }}</p>
                                <p class="mt-0.5 max-w-md break-words text-xs text-ink-muted">{{ $hit->message }}</p>
                            </td>
                            <td class="tabular-nums">{{ $hit->hits }}</td>
                            <td class="text-sm text-ink-muted">{{ $hit->last_seen_at?->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.errors.show', $hit) }}" class="text-sm font-semibold text-violet">{{ __('loop.error_open') }}</a>
                                <form method="POST" action="{{ route('admin.errors.resolve', $hit) }}" class="mt-2">
                                    @csrf
                                    <button class="text-xs font-semibold text-ink-muted hover:text-ink">{{ __('loop.error_mark_fixed') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.empty-state>
</x-admin-layout>
