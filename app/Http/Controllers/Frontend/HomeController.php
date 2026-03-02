<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $featuredFiles = Cache::remember('home_featured', 600, function () {
            return File::where('status', 'approved')
                ->where('is_featured', true)
                ->with(['category', 'screenshots'])
                ->limit(5)->get();
        });

        $latestFiles = Cache::remember('home_latest', 300, function () {
            return File::where('status', 'approved')
                ->orderByDesc('created_at')
                ->with(['category', 'screenshots', 'user'])
                ->limit(12)->get();
        });

        $latestPosts = Cache::remember('home_posts', 600, function () {
            return Post::where('is_published', true)
                ->orderByDesc('published_at')
                ->limit(5)->get();
        });

        $categories = Cache::remember('home_categories', 900, function () {
            return Category::whereNull('parent_id')
                ->where('is_active', true)
                ->with(['children' => function($q) {
                    $q->where('is_active', true);
                }])
                ->orderBy('sort_order')
                ->get()
                ->map(function ($category) {
                    $childIds = $category->children->pluck('id')->toArray();
                    $category->approved_files_count = File::where('status', 'approved')
                        ->whereIn('category_id', array_merge([$category->id], $childIds))
                        ->count();
                    return $category;
                })
                ->filter(fn ($cat) => $cat->approved_files_count > 0 || $cat->children->isNotEmpty());
        });

        $stats = Cache::remember('home_stats', 900, function () {
            return [
                'total_files' => File::where('status', 'approved')->count(),
                'total_downloads' => File::sum('download_count'),
                'total_maps' => File::where('status', 'approved')
                    ->whereHas('category', fn ($q) => $q->where('name', 'like', '%Maps%'))
                    ->count(),
            ];
        });

        return view('frontend.home', compact('featuredFiles', 'latestFiles', 'latestPosts', 'categories', 'stats'));
    }
}
