<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $stories = Article::query()
            ->visibleTo($user)
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('stories.index', [
            'stories' => $stories,
        ]);
    }

    public function show(Request $request, Article $article): View
    {
        abort_unless($article->isPublished(), 404);

        $user = $request->user();
        $country = $user?->country ?: session('preferred_country', 'TZ');
        abort_unless(
            in_array($article->audience, [Article::AUDIENCE_MEMBERS, Article::AUDIENCE_ALL], true)
            && ($article->country === null || $article->country === $country),
            404
        );

        $more = Article::query()
            ->visibleTo($user, $country)
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at')
            ->take(6)
            ->get();

        return view('stories.show', [
            'article' => $article,
            'more' => $more,
        ]);
    }
}
