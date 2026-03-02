<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Demo;
use App\Models\Category;
use App\Models\Download;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DemoController extends Controller
{
    public function index(Request $request)
    {
        $query = Demo::where('status', 'approved')->with(['category', 'user', 'tags']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('map_name', 'like', "%{$search}%")
                  ->orWhere('team_axis', 'like', "%{$search}%")
                  ->orWhere('team_allies', 'like', "%{$search}%")
                  ->orWhere('recorder_name', 'like', "%{$search}%");
            });
        }

        if ($categorySlug = $request->input('category')) {
            $category = Category::where('slug', $categorySlug)->where('type', 'demo')->first();
            if ($category) {
                $ids = collect([$category->id])->merge($category->children->pluck('id'));
                $query->whereIn('category_id', $ids);
            }
        }

        if ($game = $request->input('game')) { $query->where('game', $game); }
        if ($mod = $request->input('mod')) { $query->where('mod_name', $mod); }
        if ($gametype = $request->input('gametype')) { $query->where('gametype', $gametype); }
        if ($format = $request->input('format')) { $query->where('match_format', $format); }
        if ($demoFormat = $request->input('demo_format')) { $query->where('demo_format', $demoFormat); }
        if ($map = $request->input('map')) { $query->where('map_name', 'like', "%{$map}%"); }
        if ($tag = $request->input('tag')) { $query->whereHas('tags', fn ($q) => $q->where('slug', $tag)); }

        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'downloads' => $query->orderByDesc('download_count'),
            'rating' => $query->orderByDesc('average_rating'),
            'match_date' => $query->orderByDesc('match_date'),
            'name_asc' => $query->orderBy('title', 'asc'),
            'name_desc' => $query->orderBy('title', 'desc'),
            'size_desc' => $query->orderByDesc('file_size'),
            default => $query->orderByDesc('created_at'),
        };

        $demos = $query->paginate(24)->withQueryString();

        $categories = Category::where('type', 'demo')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        $games = Demo::where('status', 'approved')->distinct()->pluck('game')->filter();
        $mods = Demo::where('status', 'approved')->whereNotNull('mod_name')->distinct()->pluck('mod_name')->filter();
        $gametypes = Demo::where('status', 'approved')->whereNotNull('gametype')->distinct()->pluck('gametype')->filter();

        return view('frontend.demos.index', compact('demos', 'categories', 'games', 'mods', 'gametypes'));
    }

    public function show(Demo $demo)
    {
        abort_unless($demo->status === 'approved', 404);
        $demo->load(['category.parent', 'screenshots', 'user', 'tags', 'comments.user']);
        $demo->incrementViews();

        $related = Demo::where('status', 'approved')
            ->where('id', '!=', $demo->id)
            ->where(function ($q) use ($demo) {
                $q->where('category_id', $demo->category_id)
                  ->orWhere('map_name', $demo->map_name);
            })
            ->limit(6)->get();

        return view('frontend.demos.show', compact('demo', 'related'));
    }

    public function download(Demo $demo)
    {
        abort_unless($demo->status === 'approved', 404);

        try {
            Download::create([
                'file_id' => null,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'referer' => request()->header('referer'),
            ]);
        } catch (\Exception $e) {}

        $demo->incrementDownloads();
        if ($demo->user) { $demo->user->increment('total_downloads'); }

        $disk = Storage::disk('s3');
        $path = $demo->file_path;
        $fileName = $demo->file_name ?? ($demo->slug . '.' . ($demo->file_extension ?? 'dm_84'));

        $url = $disk->temporaryUrl($path, now()->addMinutes(60), [
            'ResponseContentDisposition' => 'attachment; filename="' . $fileName . '"',
            'ResponseContentType' => 'application/octet-stream',
        ]);

        return redirect($url);
    }

    public function upload()
    {
        $categories = Category::where('type', 'demo')
            ->where('is_active', true)
            ->with('parent')
            ->orderBy('sort_order')
            ->get();

        return view('frontend.demos.upload', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'category_id' => 'required|exists:categories,id',
            'game' => 'required|string|max:50',
            'map_name' => 'nullable|string|max:100',
            'mod_name' => 'nullable|string|max:50',
            'gametype' => 'nullable|string|max:50',
            'match_format' => 'nullable|string|max:20',
            'team_axis' => 'nullable|string|max:100',
            'team_allies' => 'nullable|string|max:100',
            'match_date' => 'nullable|date',
            'match_source' => 'nullable|string|max:255',
            'match_source_url' => 'nullable|url|max:255',
            'recorder_name' => 'nullable|string|max:100',
            'server_name' => 'nullable|string|max:255',
            'file' => 'required|file|max:' . (config('app.max_upload_size', 500) * 1024),
            'screenshots.*' => 'nullable|image|max:10240',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:50',
        ]);

        $uploadedFile = $request->file('file');
        $hash = hash_file('sha256', $uploadedFile->getRealPath());
        $originalName = $uploadedFile->getClientOriginalName();
        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        $demoFormat = $this->detectDemoFormat($originalName, $extension);
        $path = $uploadedFile->store('demos/' . date('Y/m'), 's3');

        $demo = Demo::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'game' => $request->game,
            'map_name' => $request->map_name,
            'mod_name' => $request->mod_name,
            'gametype' => $request->gametype,
            'match_format' => $request->match_format,
            'team_axis' => $request->team_axis,
            'team_allies' => $request->team_allies,
            'match_date' => $request->match_date,
            'match_source' => $request->match_source,
            'match_source_url' => $request->match_source_url,
            'recorder_name' => $request->recorder_name,
            'server_name' => $request->server_name,
            'file_path' => $path,
            'file_name' => $originalName,
            'file_extension' => $extension,
            'file_size' => $uploadedFile->getSize(),
            'file_hash' => $hash,
            'demo_format' => $demoFormat,
            'status' => 'pending',
        ]);

        if ($request->hasFile('screenshots')) {
            foreach ($request->file('screenshots') as $i => $screenshot) {
                $ssPath = $screenshot->store('demo-screenshots/' . $demo->id, 's3');
                $demo->screenshots()->create([
                    'path' => $ssPath,
                    'sort_order' => $i,
                    'is_primary' => $i === 0,
                ]);
            }
        }

        if ($request->has('tags') && is_array($request->tags)) {
            $tagIds = collect($request->tags)->filter()->unique()->map(function ($tagName) {
                return Tag::firstOrCreate(
                    ['slug' => \Illuminate\Support\Str::slug($tagName)],
                    ['name' => trim($tagName)]
                )->id;
            });
            $demo->tags()->sync($tagIds);
        }

        return redirect()->route('demos.index')->with('success', __('messages.upload_review_info'));
    }

    /**
     * Demo viewer - analyze and display demo contents.
     */
    public function viewer(Demo $demo)
    {
        abort_unless($demo->status === 'approved', 404);
        $demo->load(['category.parent', 'user', 'tags']);

        $service = app(\App\Services\DemoUploadService::class);
        $analysis = $service->analyzeFromS3($demo);

        return view('frontend.demos.viewer', compact('demo', 'analysis'));
    }

    private function detectDemoFormat(string $filename, string $extension): ?string
    {
        $knownFormats = ['dm_84', 'dm_83', 'dm_82', 'dm_60', 'tv_84'];
        if (in_array($extension, $knownFormats)) return $extension;
        foreach ($knownFormats as $format) {
            if (str_contains(strtolower($filename), '.' . $format)) return $format;
        }
        if (preg_match('/\.(dm_\d{2}|tv_\d{2})$/i', $filename, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }
}
