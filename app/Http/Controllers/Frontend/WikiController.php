<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\WikiArticle;
use App\Models\WikiCategory;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;

class WikiController extends Controller
{
    public function index(Request $request)
    {
        $query = WikiArticle::published()->with(['category', 'user']);

        if ($search = $request->input('search')) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('title', 'like', "%{$escaped}%")
                  ->orWhere('content', 'like', "%{$escaped}%")
                  ->orWhere('excerpt', 'like', "%{$escaped}%");
            });
        }

        if ($categorySlug = $request->input('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($tag = $request->input('tag')) {
            $query->whereJsonContains('tags', $tag);
        }

        $articles = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();
        $categories = WikiCategory::where('is_active', true)
            ->withCount('publishedArticles')
            ->orderBy('sort_order')
            ->get();

        return view('frontend.wiki.index', compact('articles', 'categories'));
    }

    public function show(string $slug)
    {
        $article = WikiArticle::where('slug', $slug)->published()->with(['category', 'user', 'comments.user'])->firstOrFail();
        $article->increment('view_count');

        $related = WikiArticle::published()
            ->where('wiki_category_id', $article->wiki_category_id)
            ->where('id', '!=', $article->id)
            ->limit(5)->get();

        return view('frontend.wiki.show', compact('article', 'related'));
    }

    public function create()
    {
        $categories = WikiCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('frontend.wiki.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:100000',
            'wiki_category_id' => 'required|exists:wiki_categories,id',
            'tags' => 'nullable|string|max:500',
        ]);

        $article = WikiArticle::create([
            'title' => $request->title,
            'content' => $request->content,
            'wiki_category_id' => $request->wiki_category_id,
            'user_id' => auth()->id(),
            'tags' => $request->tags ? array_map('trim', explode(',', $request->tags)) : null,
            'status' => 'pending',
        ]);

        $article->createRevision(auth()->id(), 'Initial version');

        return redirect()->route('wiki.index')
            ->with('success', __('messages.wiki_submitted') ?: 'Article submitted for review!');
        // Note: ActivityLogger::wikiSubmit() available for use
    }

    public function edit(WikiArticle $wikiArticle)
    {
        abort_if($wikiArticle->is_locked, 403, 'This article is locked.');
        $categories = WikiCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('frontend.wiki.edit', compact('wikiArticle', 'categories'));
    }

    public function update(Request $request, WikiArticle $wikiArticle)
    {
        abort_if($wikiArticle->is_locked, 403);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:100000',
            'wiki_category_id' => 'required|exists:wiki_categories,id',
            'change_summary' => 'nullable|string|max:255',
        ]);

        // Save revision before updating
        $wikiArticle->createRevision(auth()->id(), $request->change_summary);

        $wikiArticle->update([
            'title' => $request->title,
            'content' => $request->content,
            'wiki_category_id' => $request->wiki_category_id,
            'status' => 'pending', // needs re-approval after edit
        ]);

        return redirect()->route('wiki.show', $wikiArticle->slug)
            ->with('success', __('messages.wiki_updated') ?: 'Article updated and submitted for review!');
    }

    public function history(WikiArticle $wikiArticle)
    {
        $revisions = $wikiArticle->revisions()->with('user')->paginate(20);
        return view('frontend.wiki.history', compact('wikiArticle', 'revisions'));
    }
}
