@php
    $editing = $article->exists;
@endphp
<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-start gap-3">
            <x-back-icon :href="route('admin.articles.index')" />
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.admin_articles') }}</p>
                <h1 class="mt-1 font-display text-3xl font-semibold">{{ $editing ? __('loop.edit_article') : __('loop.new_article') }}</h1>
                <p class="mt-1 max-w-2xl text-ink-muted">{{ __('loop.article_language_help') }}</p>
                <p class="mt-1 text-sm text-ink-muted">{{ __('loop.article_body_help') }}</p>
            </div>
        </div>
    </x-slot>

    <form
        method="POST"
        action="{{ $editing ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
        enctype="multipart/form-data"
        class="grid gap-6 xl:grid-cols-2"
        x-data="articlePreview({
            title_en: @js(old('title_en', $article->title_en)),
            title_sw: @js(old('title_sw', $article->title_sw)),
            excerpt_en: @js(old('excerpt_en', $article->excerpt_en)),
            excerpt_sw: @js(old('excerpt_sw', $article->excerpt_sw)),
            body_en: @js(old('body_en', $article->body_en)),
            body_sw: @js(old('body_sw', $article->body_sw)),
            image: @js($article->imageUrl()),
        })"
    >
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="admin-card space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">English</p>
                <div>
                    <label class="loop-label">{{ __('loop.article_title_en') }}</label>
                    <input name="title_en" x-model="title_en" value="{{ old('title_en', $article->title_en) }}" class="loop-input">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.article_excerpt_en') }}</label>
                    <textarea name="excerpt_en" x-model="excerpt_en" rows="2" class="loop-input">{{ old('excerpt_en', $article->excerpt_en) }}</textarea>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.article_body_en') }}</label>
                    <textarea name="body_en" x-model="body_en" rows="10" class="loop-input">{{ old('body_en', $article->body_en) }}</textarea>
                </div>
            </div>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-mint-deep">Kiswahili</p>
                <div>
                    <label class="loop-label">{{ __('loop.article_title_sw') }}</label>
                    <input name="title_sw" x-model="title_sw" value="{{ old('title_sw', $article->title_sw) }}" class="loop-input">
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.article_excerpt_sw') }}</label>
                    <textarea name="excerpt_sw" x-model="excerpt_sw" rows="2" class="loop-input">{{ old('excerpt_sw', $article->excerpt_sw) }}</textarea>
                </div>
                <div>
                    <label class="loop-label">{{ __('loop.article_body_sw') }}</label>
                    <textarea name="body_sw" x-model="body_sw" rows="10" class="loop-input">{{ old('body_sw', $article->body_sw) }}</textarea>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="loop-label">{{ __('loop.article_country') }}</label>
                <select name="country" class="loop-input">
                    <option value="">{{ __('loop.all_countries') }}</option>
                    @foreach ($countries as $code => $meta)
                        <option value="{{ $code }}" @selected(old('country', $article->country) === $code)>{{ $meta['flag'] }} {{ $meta['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="loop-label">{{ __('loop.article_image') }}</label>
                <input type="file" name="image" accept="image/*" class="loop-input">
                @if ($article->imageUrl())
                    <img src="{{ $article->imageUrl() }}" alt="" class="mt-3 h-28 w-full rounded-2xl object-cover">
                @endif
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm font-semibold">
            <input type="checkbox" name="published" value="1" @checked(old('published', $article->isPublished()))>
            {{ __('loop.article_publish') }}
        </label>

        <div class="flex flex-wrap gap-3">
            <button class="admin-btn">{{ __('loop.save') }}</button>
            @if ($editing)
                <button
                    type="submit"
                    form="article-delete"
                    class="admin-btn-ghost text-coral"
                    onclick="return confirm(@js(__('loop.article_delete_confirm')))"
                >{{ __('loop.delete') }}</button>
            @endif
        </div>
        </div>

        <aside class="admin-preview h-fit xl:sticky xl:top-4">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <p class="text-sm font-semibold">{{ __('loop.article_preview') }}</p>
                <div class="flex gap-2 text-xs font-semibold">
                    <button type="button" class="admin-btn-ghost !py-1" :class="lang === 'en' && 'bg-slate-900 text-white'" @click="lang = 'en'">EN</button>
                    <button type="button" class="admin-btn-ghost !py-1" :class="lang === 'sw' && 'bg-slate-900 text-white'" @click="lang = 'sw'">SW</button>
                </div>
            </div>
            <div class="admin-preview__frame">
                <template x-if="image">
                    <img :src="image" alt="" class="h-40 w-full object-cover">
                </template>
                <div class="space-y-3 p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-violet">{{ __('loop.story_general') }}</p>
                    <h2 class="text-2xl font-semibold" x-text="title()"></h2>
                    <p class="text-sm text-slate-500" x-show="excerpt()" x-text="excerpt()"></p>
                    <div class="text-sm leading-relaxed" x-html="bodyHtml()"></div>
                </div>
            </div>
            @if ($editing && $article->isPublished())
                <p class="px-4 pb-4 text-xs text-slate-500">
                    <a href="{{ route('stories.show', $article) }}" class="font-semibold text-violet" target="_blank">{{ __('loop.article_open_live') }} →</a>
                </p>
            @endif
        </aside>
    </form>

    @if ($editing)
        <form id="article-delete" method="POST" action="{{ route('admin.articles.destroy', $article) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</x-admin-layout>
