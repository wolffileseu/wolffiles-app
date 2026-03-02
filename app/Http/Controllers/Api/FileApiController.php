<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\LuaScript;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileApiController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'game' => 'nullable|string',
            'category' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = File::approved()->with(['category']);
        $query->search($request->input('q'));

        if ($game = $request->input('game')) {
            $query->where('game', $game);
        }
        if ($category = $request->input('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        $files = $query->limit($request->input('limit', 10))->get();

        return response()->json([
            'count' => $files->count(),
            'results' => $files->map(fn (File $file) => $this->formatFile($file)),
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $query = File::approved()->recent()->with(['category']);

        if ($game = $request->input('game')) {
            $query->where('game', $game);
        }

        $files = $query->limit($request->input('limit', 10))->get();

        return response()->json([
            'count' => $files->count(),
            'results' => $files->map(fn (File $file) => $this->formatFile($file)),
        ]);
    }

    public function random(Request $request): JsonResponse
    {
        $query = File::approved();

        if ($game = $request->input('game')) {
            $query->where('game', $game);
        }

        $file = $query->inRandomOrder()->with(['category', 'user', 'screenshots'])->first();

        if (!$file) {
            return response()->json(['error' => 'No files found'], 404);
        }

        return response()->json(['file' => $this->formatFile($file)]);
    }

    public function top(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');
        $limit = min((int) $request->input('limit', 10), 25);

        $query = File::approved()->with(['category']);

        if ($period === 'week') {
            $query->where('published_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $query->where('published_at', '>=', now()->subMonth());
        }

        $files = $query->orderByDesc('download_count')->limit($limit)->get();

        return response()->json([
            'period' => $period,
            'results' => $files->map(fn ($f) => $this->formatFile($f)),
        ]);
    }

    public function trending(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 10), 25);

        $files = File::approved()
            ->with(['category'])
            ->orderByDesc('trending_score')
            ->limit($limit)
            ->get();

        return response()->json([
            'results' => $files->map(fn ($f) => $this->formatFile($f)),
        ]);
    }

    public function featured(): JsonResponse
    {
        $file = File::approved()
            ->where('is_featured', true)
            ->with(['category', 'user', 'screenshots'])
            ->orderByDesc('updated_at')
            ->first();

        if (!$file) {
            return response()->json(['error' => 'No featured file'], 404);
        }

        return response()->json(['file' => $this->formatFile($file)]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'total_files' => File::approved()->count(),
            'total_downloads' => (int) File::sum('download_count'),
            'total_maps' => File::approved()->whereHas('category', fn ($q) => $q->where('name', 'like', '%Map%'))->count(),
            'total_users' => \App\Models\User::count(),
            'total_comments' => \App\Models\Comment::count(),
            'total_lua_scripts' => LuaScript::where('status', 'approved')->count(),
            'pending_files' => File::where('status', 'pending')->count(),
            'categories' => Category::where('type', 'file')->active()
                ->withCount('approvedFiles')
                ->get()
                ->map(fn ($c) => ['name' => $c->name, 'files' => $c->approved_files_count]),
        ]);
    }

    public function show(File $file): JsonResponse
    {
        abort_unless($file->isApproved(), 404);
        $file->load(['category', 'screenshots', 'tags', 'user']);

        $data = $this->formatFile($file);
        $data['description'] = $file->description;
        $data['version'] = $file->version;
        $data['tags'] = $file->tags->pluck('name');
        $data['screenshots'] = $file->screenshots->map(fn ($s) => $s->url);

        return response()->json($data);
    }

    public function wikiSearch(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);

        $query = $request->input('q');
        $limit = min((int) $request->input('limit', 5), 20);

        $articles = \App\Models\WikiArticle::published()
            ->with(['category', 'user'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%")
                  ->orWhere('excerpt', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return response()->json([
            'count' => $articles->count(),
            'results' => $articles->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'slug' => $a->slug,
                'excerpt' => Str::limit(strip_tags($a->excerpt ?? $a->content), 200),
                'category' => $a->category?->name,
                'author' => $a->user?->name,
                'view_count' => $a->view_count,
                'url' => route('wiki.show', $a->slug),
            ]),
        ]);
    }

    public function tutorialSearch(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);

        $query = $request->input('q');
        $limit = min((int) $request->input('limit', 5), 20);

        $tutorials = \App\Models\Tutorial::published()
            ->with(['category', 'user'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%")
                  ->orWhere('excerpt', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return response()->json([
            'count' => $tutorials->count(),
            'results' => $tutorials->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'slug' => $t->slug,
                'excerpt' => Str::limit(strip_tags($t->excerpt ?? $t->content), 200),
                'category' => $t->category?->name,
                'difficulty' => $t->difficulty,
                'estimated_minutes' => $t->estimated_minutes,
                'author' => $t->user?->name,
                'helpful_percentage' => $t->helpful_percentage,
                'view_count' => $t->view_count,
                'url' => route('tutorials.show', $t->slug),
            ]),
        ]);
    }

    /**
     * Format file for API response
     */
    private function formatFile(File $file): array
    {
        return [
            'id' => $file->id,
            'title' => $file->title,
            'slug' => $file->slug,
            'category' => $file->category?->name,
            'game' => $file->game,
            'map_name' => $file->map_name,
            'file_size' => $file->file_size_formatted,
            'download_count' => $file->download_count,
            'average_rating' => $file->average_rating,
            'url' => route('files.show', $file),
            'download_url' => route('files.download', $file),
            'thumbnail' => $file->thumbnail_url ?? ($file->screenshots && $file->screenshots->first() ? Storage::disk('s3')->url($file->screenshots->first()->path) : null),
            'author' => $file->original_author ?? $file->user?->name,
            'published_at' => ($file->published_at ? \Carbon\Carbon::parse($file->published_at)->toIso8601String() : null),
            'mod_compatibility' => $file->mod_compatibility,
            'readme_content' => $file->readme_content,
        ];
    }
}