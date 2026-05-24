<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class UploaderTreeController extends Controller
{
    /**
     * Returns the Game -> Category tree for the Wolffiles Uploader.
     *
     * Response shape:
     *   data[].slug        -> category slug (e.g. "et")
     *   data[].name        -> display name; MUST be written to files.game
     *   data[].icon        -> optional icon ref
     *   data[].categories  -> child categories (id, slug, name, icon)
     */
    public function tree()
    {
        $tree = Cache::remember('api.uploader.tree.v1', 3600, function () {
            $games = Category::query()
                ->whereNull('parent_id')
                ->where('type', 'game')
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'icon']);

            $childrenByParent = Category::query()
                ->whereNotNull('parent_id')
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'parent_id', 'name', 'slug', 'icon'])
                ->groupBy('parent_id');

            return $games->map(fn($game) => [
                'slug'       => $game->slug,
                'name'       => $game->name,
                'icon'       => $game->icon,
                'categories' => ($childrenByParent[$game->id] ?? collect())
                    ->map(fn($c) => [
                        'id'   => $c->id,
                        'slug' => $c->slug,
                        'name' => $c->name,
                        'icon' => $c->icon,
                    ])->values()->toArray(),
            ])->values()->toArray();
        });

        return response()->json([
            'data'       => $tree,
            'fetched_at' => now()->toIso8601String(),
            'version'    => 1,
        ]);
    }
}
