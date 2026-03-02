<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = File::where('status', 'approved')->with(['category', 'screenshots', 'user', 'tags']);

        // Text search
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('map_name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('original_author', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($catId = $request->input('category_id')) {
            $category = Category::find($catId);
            if ($category) {
                $ids = collect([$category->id])->merge($category->children->pluck('id'));
                $query->whereIn('category_id', $ids);
            }
        }

        // Game filter
        if ($game = $request->input('game')) {
            $query->where('game', $game);
        }

        // Tag filter
        if ($tagSlug = $request->input('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
        }

        // Author filter
        if ($author = $request->input('author')) {
            $query->where(function ($q) use ($author) {
                $q->where('original_author', 'like', "%{$author}%")
                  ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$author}%"));
            });
        }

        // File size range (in MB)
        if ($minSize = $request->input('min_size')) {
            $query->where('file_size', '>=', $minSize * 1048576);
        }
        if ($maxSize = $request->input('max_size')) {
            $query->where('file_size', '<=', $maxSize * 1048576);
        }

        // Rating filter
        if ($minRating = $request->input('min_rating')) {
            $query->where('average_rating', '>=', $minRating);
        }

        // Date range
        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        // Has screenshots only
        if ($request->boolean('has_screenshots')) {
            $query->whereHas('screenshots');
        }

        // Sorting
        $sort = $request->input('sort', 'relevance');
        $query = match ($sort) {
            'newest' => $query->orderByDesc('created_at'),
            'oldest' => $query->orderBy('created_at'),
            'downloads' => $query->orderByDesc('download_count'),
            'rating' => $query->orderByDesc('average_rating'),
            'name_asc' => $query->orderBy('title'),
            'size_desc' => $query->orderByDesc('file_size'),
            default => $search ? $query->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END", ["%{$search}%"])->orderByDesc('download_count') : $query->orderByDesc('created_at'),
        };

        $files = $query->paginate(24)->withQueryString();

        // Data for filters
        $categories = Category::whereNull('parent_id')->where('is_active', true)
            ->with('children')->orderBy('sort_order')->get();
        $games = File::where('status', 'approved')->distinct()->pluck('game')->filter();
        $popularTags = Tag::withCount('files')->orderByDesc('files_count')->limit(20)->get();

        if ($search = $request->input('q')) {
            ActivityLogger::search($search, $files->total());
        }

        return view('frontend.search.index', compact('files', 'categories', 'games', 'popularTags'));
    }
}
