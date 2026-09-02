<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\Confirm;
use App\Support\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        return view('admin.articles.index', [
            'articles' => Article::query()->latest('updated_at')->paginate(\App\Support\AdminPagination::perPage(request()))->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.articles.form', [
            'article' => new Article(['audience' => Article::AUDIENCE_MEMBERS]),
            'countries' => Countries::formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = new Article;
        $this->persist($request, $article);

        return redirect()->route('admin.articles.index')->with('confirm', Confirm::make(
            __('loop.article_saved_title'),
            __('loop.article_saved_body'),
            __('loop.done'),
            route('admin.articles.index'),
        ));
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', [
            'article' => $article,
            'countries' => Countries::formOptions($article->country),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->persist($request, $article);

        return redirect()->route('admin.articles.index')->with('confirm', Confirm::make(
            __('loop.article_saved_title'),
            __('loop.article_saved_body'),
            __('loop.done'),
            route('admin.articles.index'),
        ));
    }

    public function destroy(Article $article): RedirectResponse
    {
        if ($article->image_path) {
            Storage::disk('public')->delete($article->image_path);
        }
        $article->delete();

        return redirect()->route('admin.articles.index')->with('confirm', Confirm::make(
            __('loop.article_deleted_title'),
            __('loop.article_deleted_body'),
            __('loop.done'),
            route('admin.articles.index'),
            false,
        ));
    }

    private function persist(Request $request, Article $article): void
    {
        $request->merge([
            'country' => $request->filled('country') ? $request->input('country') : null,
        ]);

        $data = $request->validate([
            'title_en' => ['required_without:title_sw', 'nullable', 'string', 'max:160'],
            'title_sw' => ['required_without:title_en', 'nullable', 'string', 'max:160'],
            'excerpt_en' => ['nullable', 'string', 'max:400'],
            'excerpt_sw' => ['nullable', 'string', 'max:400'],
            'body_en' => ['nullable', 'string'],
            'body_sw' => ['nullable', 'string'],
            'country' => ['nullable', Countries::formRule($article->country)],
            'audience' => ['nullable', 'in:members,owners,all'],
            'image' => ['nullable', 'image', 'max:4096'],
            'published' => ['nullable', 'boolean'],
        ]);

        $titleEn = trim((string) ($data['title_en'] ?? ''));
        $titleSw = trim((string) ($data['title_sw'] ?? ''));

        $article->fill([
            'user_id' => $article->user_id ?: $request->user()->id,
            'title_en' => $titleEn !== '' ? $titleEn : null,
            'title_sw' => $titleSw !== '' ? $titleSw : null,
            'excerpt_en' => $data['excerpt_en'] ?? null,
            'excerpt_sw' => $data['excerpt_sw'] ?? null,
            'body_en' => $data['body_en'] ?? null,
            'body_sw' => $data['body_sw'] ?? null,
            'country' => $data['country'] ?: null,
            'audience' => $data['audience'] ?? Article::AUDIENCE_MEMBERS,
            'published_at' => $request->boolean('published')
                ? ($article->published_at ?: now())
                : null,
        ]);
        $article->slug = Article::uniqueSlug($titleEn ?: $titleSw, $article->id);

        if ($request->hasFile('image')) {
            if ($article->image_path) {
                Storage::disk('public')->delete($article->image_path);
            }
            $article->image_path = $request->file('image')->store('article-images', 'public');
        }

        $article->save();
    }
}
