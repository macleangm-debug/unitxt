<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold">{{ __('loop.admin_articles') }}</h1>
                <p class="mt-1 text-ink-muted">{{ __('loop.admin_articles_blurb') }}</p>
            </div>
            <a href="{{ route('admin.articles.create') }}" class="admin-btn">{{ __('loop.new_article') }}</a>
        </div>
    </x-slot>

    <x-admin.empty-state :empty="$articles->isEmpty()" :title="__('loop.no_articles_yet')">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>{{ __('loop.stories') }}</th>
                        <th>{{ __('loop.country') }}</th>
                        <th>{{ __('loop.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($articles as $article)
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $article->title() ?: '—' }}</p>
                                <p class="text-xs text-ink-muted">{{ $article->excerpt() }}</p>
                            </td>
                            <td>{{ $article->countryLabel() }}</td>
                            <td>
                                @if ($article->isPublished())
                                    <span class="text-mint-deep">{{ $article->published_at->format('d M Y') }}</span>
                                @else
                                    <span class="text-ink-muted">{{ __('loop.unpublished') }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('admin.articles.edit', $article) }}" class="text-sm font-semibold text-violet">{{ __('loop.edit') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            <x-admin.table-pager :paginator="$articles" />
        </div>
    </x-admin.empty-state>
</x-admin-layout>
