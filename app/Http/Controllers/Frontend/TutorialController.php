<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use App\Models\TutorialCategory;
use App\Models\TutorialVote;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    public function index(Request $request)
    {
        $query = Tutorial::published()->with(['category', 'user']);

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

        if ($difficulty = $request->input('difficulty')) {
            $query->where('difficulty', $difficulty);
        }

        if ($tag = $request->input('tag')) {
            $query->whereJsonContains('tags', $tag);
        }

        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'popular' => $query->orderByDesc('view_count'),
            'helpful' => $query->orderByDesc('helpful_count'),
            'oldest' => $query->orderBy('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        $tutorials = $query->paginate(12)->withQueryString();
        $categories = TutorialCategory::where('is_active', true)
            ->withCount('publishedTutorials')
            ->orderBy('sort_order')
            ->get();

        return view('frontend.tutorials.index', compact('tutorials', 'categories'));
    }

    public function show(string $slug)
    {
        $tutorial = Tutorial::where('slug', $slug)->published()
            ->with(['category', 'user', 'steps', 'comments.user', 'seriesParts' => fn ($q) => $q->published()])
            ->firstOrFail();

        $tutorial->increment('view_count');

        $userVote = auth()->check()
            ? TutorialVote::where('tutorial_id', $tutorial->id)->where('user_id', auth()->id())->first()
            : null;

        $related = Tutorial::published()
            ->where('tutorial_category_id', $tutorial->tutorial_category_id)
            ->where('id', '!=', $tutorial->id)
            ->limit(4)->get();

        return view('frontend.tutorials.show', compact('tutorial', 'userVote', 'related'));
    }

    public function create()
    {
        $categories = TutorialCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('frontend.tutorials.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tutorial_category_id' => 'required|exists:tutorial_categories,id',
            'difficulty' => 'required|in:beginner,intermediate,advanced',
            'estimated_minutes' => 'nullable|integer|min:1',
            'prerequisites' => 'nullable|string|max:1000',
            'youtube_url' => 'nullable|url',
            'tags' => 'nullable|string',
        ]);

        $tutorial = Tutorial::create([
            'title' => $request->title,
            'content' => $request->content,
            'tutorial_category_id' => $request->tutorial_category_id,
            'difficulty' => $request->difficulty,
            'estimated_minutes' => $request->estimated_minutes,
            'prerequisites' => $request->prerequisites,
            'youtube_url' => $request->youtube_url,
            'user_id' => auth()->id(),
            'tags' => $request->tags ? array_map('trim', explode(',', $request->tags)) : null,
            'status' => 'pending',
        ]);

        return redirect()->route('tutorials.index')
            ->with('success', __('messages.tutorial_submitted') ?: 'Tutorial submitted for review!');
    }

    public function vote(Request $request, Tutorial $tutorial)
    {
        $request->validate(['is_helpful' => 'required|boolean']);

        $vote = TutorialVote::updateOrCreate(
            ['tutorial_id' => $tutorial->id, 'user_id' => auth()->id()],
            ['is_helpful' => $request->is_helpful]
        );

        // Recalculate counts
        $tutorial->update([
            'helpful_count' => $tutorial->votes()->where('is_helpful', true)->count(),
            'not_helpful_count' => $tutorial->votes()->where('is_helpful', false)->count(),
        ]);

        return back()->with('success', 'Thanks for your feedback!');
    }
}
