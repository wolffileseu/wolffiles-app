<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Http\Middleware\TrackRecentlyViewed;
use App\Models\File;
use App\Models\Category;
use App\Models\Download;
use App\Models\Rating;
use App\Models\Tag;
use App\Services\ActivityLogger;
use App\Services\AutoApproveService;
use App\Services\FileUploadService;
use App\Services\FileValidationService;
use App\Services\SeoService;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use App\Notifications\NewFileUploaded;
use App\Models\User;
use App\Notifications\DownloadMilestone;
use Illuminate\Support\Facades\Storage;
class FileController extends Controller
{
    public function index(Request $request)
    {
        $query = File::where('status', 'approved')->with(['category', 'screenshots', 'user', 'tags']);
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('map_name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }
        if ($categorySlug = $request->input('category')) {
            $category = Category::where('slug', $categorySlug)->firstOrFail();
            $ids = collect([$category->id])->merge($category->children->pluck('id'));
            $query->whereIn('category_id', $ids);
        }
        if ($game = $request->input('game')) {
            $query->where('game', $game);
        }
        if ($tag = $request->input('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tag));
        }
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
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->withCount(['files as approved_files_count' => fn ($q2) => $q2->where('status', 'approved')])])
            ->withCount(['files as approved_files_count' => fn ($q) => $q->where('status', 'approved')])
            ->orderBy('sort_order')
            ->get();
        $games = File::where('status', 'approved')->distinct()->pluck('game')->filter();
        return view('frontend.files.index', compact('files', 'categories', 'games'));
    }
    public function show(File $file)
    {
        abort_unless($file->status === 'approved', 404);
        $file->incrementViews();
        $file->load(['category.parent', 'screenshots', 'user', 'tags', 'comments.user']);
        TrackRecentlyViewed::addFile($file->id);
        $related = File::where('status', 'approved')
            ->where('category_id', $file->category_id)
            ->where('id', '!=', $file->id)
            ->limit(6)->get();
        $userRating = auth()->check() ? Rating::where('user_id', auth()->id())->where('file_id', $file->id)->first() : null;
        $isFavorited = auth()->check() ? $file->favorites()->where('user_id', auth()->id())->exists() : false;
        $seo = SeoService::forFile($file);
        $jsonLd = ['type' => 'file', 'file' => $file];

        // Live-Server-Stats for this map (Issue #5)
        // Cached 10 min — stats change slowly + page gets heavy traffic
        $mapLiveStats = $this->getMapLiveStats($file);

        return view('frontend.files.show', compact(
            'file', 'related', 'userRating', 'isFavorited', 'seo', 'jsonLd', 'mapLiveStats'
        ));
    }

    /**
     * Aggregate live-server play statistics for a file's map_name.
     * Returns null when the file has no map_name or no tracker data exists.
     *
     * @return array{total_plays:int,active_servers:int,peak_players:int,last_played_at:?\Carbon\Carbon,top_servers:\Illuminate\Support\Collection}|null
     */
    private function getMapLiveStats(File $file): ?array
    {
        $mapName = trim((string) $file->map_name);
        if ($mapName === '') {
            return null;
        }

        return \Illuminate\Support\Facades\Cache::remember(
            "map-live-stats:{$mapName}",
            now()->addMinutes(10),
            function () use ($mapName) {
                $agg = \Illuminate\Support\Facades\DB::table('tracker_server_map_stats')
                    ->where('map_name', $mapName)
                    ->selectRaw('
                        COALESCE(SUM(times_played), 0) as total_plays,
                        COUNT(DISTINCT server_id) as active_servers,
                        COALESCE(MAX(peak_players), 0) as peak_players,
                        MAX(last_played_at) as last_played_at
                    ')
                    ->first();

                // No tracker data for this map at all? Return null so the widget hides.
                if (!$agg || (int) $agg->total_plays === 0) {
                    return null;
                }

                // Top 5 servers for this map (most plays first)
                $topServers = \Illuminate\Support\Facades\DB::table('tracker_server_map_stats as sms')
                    ->join('tracker_servers as s', 's.id', '=', 'sms.server_id')
                    ->where('sms.map_name', $mapName)
                    ->where('s.status', 'online')
                    ->orderByDesc('sms.times_played')
                    ->limit(5)
                    ->get([
                        's.id as server_id',
                        's.hostname_clean',
                        's.country_code',
                        's.current_players',
                        's.max_players',
                        'sms.times_played',
                        'sms.last_played_at',
                    ]);

                return [
                    'total_plays'    => (int) $agg->total_plays,
                    'active_servers' => (int) $agg->active_servers,
                    'peak_players'   => (int) $agg->peak_players,
                    'last_played_at' => $agg->last_played_at ? \Carbon\Carbon::parse($agg->last_played_at) : null,
                    'top_servers'    => $topServers,
                ];
            }
        );
    }
    public function download(File $file)
    {
        abort_unless($file->status === 'approved', 404);
        $file->incrementViews();
        Download::create([
            'file_id' => $file->id,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
        ]);
        $file->incrementDownloads();
        ActivityLogger::fileDownload($file);
        StatisticsService::recordDownload($file->id);
        if ($file->user) {
            $file->user->increment('total_downloads');
        }
        // Check download milestones
        $milestones = [100, 500, 1000, 5000, 10000];
        if ($file->user && in_array($file->download_count, $milestones)) {
            $file->user->notify(new DownloadMilestone($file, $file->download_count));
        }
        // Always use S3 signed URL redirect (prevents PHP memory/timeout issues on large files)
        $disk = Storage::disk('s3');
        $path = $file->file_path;
        $fileName = $file->file_name ?? ($file->slug . '.pk3');
        // Sanitize filename for Content-Disposition header
        $safeFileName = preg_replace('/[^\w.\-]/', '_', $fileName);
        $url = $disk->temporaryUrl($path, now()->addMinutes(60), [
			'ResponseContentDisposition' => 'attachment; filename="' . $safeFileName . '"',
            'ResponseContentType' => 'application/octet-stream',
        ]);
        return redirect($url);
    }
    /**
     * Generate a WebVTT file for scrub-preview thumbnails (YouTube-style).
     * Maps timecodes to crop regions in the existing contact sheet image.
     */
    public function scrubVtt(File $file)
    {
        abort_unless($file->status === 'approved', 404);
        abort_unless($file->isPlayableVideo(), 404);
        abort_unless($file->playable_duration_seconds > 0, 404, 'No duration available');

        $sheet = $file->primaryScreenshot;
        abort_unless($sheet, 404, 'No contact sheet available');

        // Contact sheets are generated as 5x5 grid, 320x180 per tile
        // (matches AnalyzeUploadedFile + GenerateVideoThumbnails command)
        $cols = 5;
        $rows = 5;
        $tileW = 320;
        $tileH = 180;
        $totalTiles = $cols * $rows;

        $duration = (int) $file->playable_duration_seconds;
        $secondsPerTile = $duration / $totalTiles;

        // Sheet URL (CDN/S3 — must be publicly accessible to the browser)
        $sheetUrl = $sheet->url;

        $vtt = "WEBVTT

";
        for ($i = 0; $i < $totalTiles; $i++) {
            $startSec = (int) round($i * $secondsPerTile);
            $endSec = (int) round(($i + 1) * $secondsPerTile);
            if ($endSec > $duration) {
                $endSec = $duration;
            }
            $col = $i % $cols;
            $row = (int) floor($i / $cols);
            $x = $col * $tileW;
            $y = $row * $tileH;

            $vtt .= $this->formatVttTime($startSec) . " --> " . $this->formatVttTime($endSec) . "
";
            $vtt .= $sheetUrl . "#xywh=" . $x . "," . $y . "," . $tileW . "," . $tileH . "

";
        }

        return response($vtt, 200, [
            'Content-Type' => 'text/vtt; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    private function formatVttTime(int $totalSeconds): string
    {
        $h = (int) floor($totalSeconds / 3600);
        $m = (int) floor(($totalSeconds % 3600) / 60);
        $s = $totalSeconds % 60;
        return sprintf('%02d:%02d:%02d.000', $h, $m, $s);
    }

    /**
     * Stream the playable video version (inline, no download counter).
     * Returns a signed S3 redirect with 6h TTL.
     */
    public function stream(File $file)
    {
        abort_unless($file->status === 'approved', 404);
        abort_unless($file->isPlayableVideo(), 404, 'No playable video available for this file');

        $url = $file->playable_url;
        abort_unless($url, 503, 'Stream URL could not be generated');

        return redirect($url);
    }

    public function upload()
    {
        $categories = Category::where('is_active', true)->with('parent')->orderBy('sort_order')->get();
        return view('frontend.files.upload', compact('categories'));
    }
    public function store(Request $request)
    {
        // Multipart-Upload: S3-Key kommt aus Hidden-Fields
        $isMultipart = $request->filled('file_s3_key');

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'category_id' => 'required|exists:categories,id',
            'game' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:50',
            'original_author' => 'nullable|string|max:255',
            'screenshots.*' => 'nullable|image|max:10240',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:50',
        ];

        if ($isMultipart) {
            $rules['file_s3_key'] = 'required|string|max:500';
            $rules['file_filename'] = 'required|string|max:255';
            $rules['file_size'] = 'required|integer|min:1';
            $rules['file_hash'] = 'nullable|string|size:64';
            $rules['file_content_type'] = 'nullable|string|max:200';
        } else {
            $rules['file'] = 'required|file|max:' . (config('app.max_upload_size', 500) * 1024);
        }

        $request->validate($rules);

        if ($isMultipart) {
            $hash = $request->input('file_hash') ?: '';
            // Duplicate-Check — hash kann leer sein für > 1GB Files
            if ($hash) {
                $duplicate = FileValidationService::findDuplicate($hash);
                if ($duplicate) {
                    return back()->withErrors(['file' => __('messages.duplicate_detected', ['title' => $duplicate->title])])->withInput();
                }
            }

            $file = app(FileUploadService::class)->uploadFromS3(
                s3Key: $request->input('file_s3_key'),
                originalFilename: $request->input('file_filename'),
                fileSize: (int) $request->input('file_size'),
                fileHash: $hash,
                contentType: $request->input('file_content_type'),
                data: $request->only(['title', 'description', 'category_id', 'game', 'version', 'original_author']),
                userId: auth()->id(),
                screenshots: $request->file('screenshots', [])
            );
        } else {
            $uploadedFile = $request->file('file');
            $hash = hash_file('sha256', $uploadedFile->getRealPath());
            $duplicate = FileValidationService::findDuplicate($hash);
            if ($duplicate) {
                return back()->withErrors(['file' => __('messages.duplicate_detected', ['title' => $duplicate->title])])->withInput();
            }
            $file = app(FileUploadService::class)->upload(
                $uploadedFile,
                array_merge($request->only(['title', 'description', 'category_id', 'game', 'version', 'original_author']), [
                    'file_hash' => $hash,
                ]),
                auth()->id(),
                $request->file('screenshots', [])
            );
        }
        // Sync tags
        if ($request->has('tags') && is_array($request->tags)) {
            $tagIds = collect($request->tags)
                ->filter()
                ->unique()
                ->map(function ($tagName) {
                    return Tag::firstOrCreate(
                        ['slug' => \Illuminate\Support\Str::slug($tagName)],
                        ['name' => trim($tagName)]
                    )->id;
                });
            $file->tags()->sync($tagIds);
        }
        AutoApproveService::processUpload($file);
        ActivityLogger::fileUpload($file);
        // Notify admins about new upload
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new NewFileUploaded($file, auth()->user()));
        }
        $file->refresh();

        if ($file->isApproved()) {
            return redirect()->route('files.show', $file)
                ->with('success', __('messages.file_uploaded_success'));
        }

        return redirect()->route('files.upload')
            ->with('success', __('messages.file_uploaded_pending'));
    }
    public function rate(Request $request, File $file)
    {
        $request->validate(['rating' => 'required|integer|min:1|max:5']);
        Rating::updateOrCreate(
            ['user_id' => auth()->id(), 'file_id' => $file->id],
            ['rating' => $request->rating]
        );
            $file->recalculateRating();
        ActivityLogger::rate($file, $request->rating);
        return back()->with('success', __('messages.rating_saved'));
    }
    public function favorite(File $file)
    {
        $exists = $file->favorites()->where('user_id', auth()->id())->first();
        if ($exists) {
            $exists->delete();
            ActivityLogger::unfavorite($file);
            return back()->with('success', __('messages.removed_from_favorites'));
        }
        $file->favorites()->create(['user_id' => auth()->id()]);
        ActivityLogger::favorite($file);
        return back()->with('success', __('messages.added_to_favorites'));
    }
}
