<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('loop.admin_operations') }}</p>
            <h1 class="mt-1">{{ __('loop.admin_privacy') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('loop.admin_privacy_blurb') }}</p>
        </div>
    </x-slot>

    <section class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>{{ __('loop.when') }}</th>
                        <th>{{ __('loop.member') }}</th>
                        <th>{{ __('loop.privacy_request_kind') }}</th>
                        <th>{{ __('loop.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $row)
                        <tr>
                            <td>{{ $row->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ $row->user?->name }} {{ $row->user?->full_phone }}</td>
                            <td>{{ $row->kind }}</td>
                            <td>{{ $row->status }}</td>
                            <td>
                                @if ($row->status === 'open')
                                    <form method="POST" action="{{ route('admin.privacy.resolve', $row) }}">
                                        @csrf
                                        <button class="text-sm font-semibold text-violet">{{ __('loop.resolve') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-ink-muted">{{ __('loop.no_privacy_requests') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $requests->links() }}</div>
    </section>
</x-admin-layout>
