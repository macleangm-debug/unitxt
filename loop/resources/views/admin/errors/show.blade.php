<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">{{ __('loop.admin_errors') }}</p>
                <h1>{{ $hit->method }} {{ $hit->path }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ __('loop.admin_errors_detail_blurb') }}</p>
            </div>
            <a href="{{ route('admin.errors.index') }}" class="admin-btn-ghost !py-2">{{ __('loop.back') }}</a>
        </div>
    </x-slot>

    <div class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
        <section class="admin-card space-y-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ __('loop.error_response') }}</p>
                <p class="mt-1 font-display text-2xl font-semibold">{{ $hit->status_code }} · {{ class_basename($hit->exception_class) }}</p>
                <p class="mt-2 text-sm leading-relaxed text-ink">{{ $hit->message }}</p>
            </div>
            <dl class="admin-dl">
                <dt>{{ __('loop.error_page') }}</dt>
                <dd class="break-all">{{ $hit->method }} {{ $hit->path }}</dd>
                <dt>{{ __('loop.error_url') }}</dt>
                <dd class="break-all">{{ $hit->url }}</dd>
                @if ($hit->route_name)
                    <dt>{{ __('loop.error_route') }}</dt>
                    <dd>{{ $hit->route_name }}</dd>
                @endif
                <dt>{{ __('loop.error_hits') }}</dt>
                <dd>{{ $hit->hits }}</dd>
                <dt>{{ __('loop.error_first_seen') }}</dt>
                <dd>{{ $hit->first_seen_at?->format('d M Y H:i') }}</dd>
                <dt>{{ __('loop.when') }}</dt>
                <dd>{{ $hit->last_seen_at?->diffForHumans() }}</dd>
            </dl>
            @if ($hit->file)
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ __('loop.error_file') }}</p>
                    <p class="mt-1 break-all font-mono text-xs text-ink-muted">{{ $hit->file }}{{ $hit->line ? ':'.$hit->line : '' }}</p>
                </div>
            @endif
        </section>

        <section class="admin-card space-y-3">
            <p class="text-sm text-ink-muted">{{ __('loop.admin_errors_resolve_help') }}</p>
            @if ($hit->url)
                <a href="{{ $hit->url }}" target="_blank" rel="noopener" class="admin-btn-ghost inline-flex w-full justify-center">{{ __('loop.error_visit') }}</a>
            @endif
            <form method="POST" action="{{ route('admin.errors.resolve', $hit) }}">
                @csrf
                <button class="admin-btn w-full">{{ __('loop.error_mark_fixed') }}</button>
            </form>
        </section>
    </div>

    @if ($hit->sample_trace)
        <section class="admin-card mt-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ __('loop.error_trace') }}</p>
            <pre class="mt-3 max-h-[28rem] overflow-auto whitespace-pre-wrap break-all font-mono text-[11px] leading-relaxed text-slate-600">{{ $hit->sample_trace }}</pre>
        </section>
    @endif
</x-admin-layout>
