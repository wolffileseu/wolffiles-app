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
        // MediaWiki-Style: /wiki → wenn ein Artikel mit slug 'hauptseite' oder 'main-page' existiert, dorthin redirecten.
        // Erst wenn der User explizit Filter setzt (search/category/tag/sort), bleibt er auf der Index-Liste.
        $hasFilter = $request->hasAny(['search', 'category', 'tag', 'sort']);
        if (!$hasFilter) {
            $home = WikiArticle::published()
                ->whereIn('slug', ['hauptseite', 'main-page', 'home'])
                ->orderByRaw("FIELD(slug, 'hauptseite', 'main-page', 'home')")
                ->first();
            if ($home) {
                return redirect()->route('wiki.show', $home->slug);
            }
        }

        $query = WikiArticle::published()->with(['category', 'user']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
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
            'content' => 'required|string',
            'wiki_category_id' => 'required|exists:wiki_categories,id',
            'tags' => 'nullable|string',
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
            'content' => 'required|string',
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

    /**
     * Special:Recent Changes — letzte Edits chronologisch.
     */
    public function recentChanges(Request $request)
    {
        $days  = (int) $request->input('days', 30);
        $limit = min(500, max(10, (int) $request->input('limit', 100)));

        $revisions = \App\Models\WikiRevision::with(['article', 'user'])
            ->whereHas('article', fn($q) => $q->whereNull('deleted_at'))
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return view('frontend.wiki.special.recent', compact('revisions', 'days', 'limit'));
    }

    /**
     * Special:Random — redirect zu zufälligem published Artikel.
     */
    public function randomPage()
    {
        $article = WikiArticle::published()
            ->where('namespace', 'main')
            ->where('is_redirect', false)
            ->inRandomOrder()
            ->first();

        if (!$article) {
            return redirect()->route('wiki.special.all')
                ->with('info', 'Es gibt noch keine Artikel.');
        }

        return redirect()->route('wiki.show', $article->slug);
    }

    /**
     * Special:AllPages — alphabetisch sortierte Liste aller published Artikel.
     */
    public function allPages(Request $request)
    {
        $namespace = $request->input('ns', 'main');
        $startsWith = $request->input('from', '');

        $query = WikiArticle::published()->where('namespace', $namespace);
        if ($startsWith !== '') {
            $query->where('title', 'like', $startsWith . '%');
        }

        $articles = $query->orderBy('title')->paginate(100)->withQueryString();

        // Alphabet-Navigation
        $letters = range('A', 'Z');

        return view('frontend.wiki.special.all', compact('articles', 'namespace', 'startsWith', 'letters'));
    }

    /**
     * Special:WhatLinksHere — alle Artikel die auf $slug verlinken.
     */
    public function whatLinksHere(string $slug)
    {
        $article = WikiArticle::where('slug', $slug)->firstOrFail();

        $incoming = $article->incomingLinks()
            ->with('fromArticle.user')
            ->whereHas('fromArticle', fn($q) => $q->where('status', 'published')->whereNull('deleted_at'))
            ->paginate(50);

        $redirects = $article->redirectsHere()->get();

        return view('frontend.wiki.special.whatlinkshere', compact('article', 'incoming', 'redirects'));
    }
}
