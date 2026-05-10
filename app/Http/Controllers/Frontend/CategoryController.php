<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\File;
use App\Services\SeoService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)
                  ->withCount(['files as approved_files_count' => function ($q2) {
                      $q2->where('status', 'approved');
                  }])
                  ->orderBy('sort_order');
            }])
            ->withCount(['files as approved_files_count' => function ($q) {
                $q->where('status', 'approved');
            }])
            ->orderBy('sort_order')
            ->get();

        // Calculate total files including children
        foreach ($categories as $category) {
            $childTotal = $category->children->sum('approved_files_count');
            $category->approved_files_count = $category->approved_files_count + $childTotal;
        }

        return view('frontend.categories.index', compact('categories'));
    }


    public function show(Request $request, Category $category)
    {
        $category->load(['children' => function ($q) {
            $q->where('is_active', true)
              ->withCount(['files as approved_files_count' => function ($q2) {
                  $q2->where('status', 'approved');
              }])
              ->orderBy('sort_order');
        }, 'parent']);

        $query = File::where('status', 'approved')
            ->where('category_id', $category->id)
            ->with(['screenshots', 'user', 'category']);

        // #13 Sort by user choice
        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'downloads' => $query->orderByDesc('download_count'),
            'rating' => $query->orderByDesc('average_rating'),
            'name_asc' => $query->orderBy('title', 'asc'),
            'name_desc' => $query->orderBy('title', 'desc'),
            'size_desc' => $query->orderByDesc('file_size'),
            'size_asc' => $query->orderBy('file_size', 'asc'),
            default => $query->orderByDesc('created_at'),
        };

        $files = $query->paginate(24)->withQueryString();

        // #30 SEO
        $seo = SeoService::forCategory($category);

        return view('frontend.categories.show', compact('category', 'files', 'seo'));
    }
}
