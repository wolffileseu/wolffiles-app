<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryApiController extends Controller
{
    public function index()
    {
        $categories = Cache::remember('api.categories.v2', 3600, function () {
            $parents = Category::whereNull('parent_id')
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);

            $children = Category::whereNotNull('parent_id')
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'parent_id', 'name', 'slug']);

            $result = [];
            foreach ($parents as $parent) {
                $result[] = [
                    'id'       => $parent->id,
                    'name'     => $parent->name,
                    'slug'     => $parent->slug,
                    'children' => $children->where('parent_id', $parent->id)
                        ->values()
                        ->map(fn($c) => [
                            'id'   => $c->id,
                            'name' => $c->name,
                            'slug' => $c->slug,
                        ])->toArray(),
                ];
            }
            return $result;
        });

        return response()->json(['data' => $categories]);
    }
}
