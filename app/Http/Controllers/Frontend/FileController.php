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
        return view('frontend.files.show', compact('file', 'related', 'userRating', 'isFavorited', 'seo', 'jsonLd'));
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
        $url = $disk->temporaryUrl($path, now()->addMinutes(60), [
            'ResponseContentDisposition' => 'attachment; filename="' . $fileName . '"',
            'ResponseContentType' => 'application/octet-stream',
        ]);
        return redirect($url);
    }
    public function upload()
    {
        $categories = Category::where('is_active', true)->with('parent')->orderBy('sort_order')->get();
        return view('frontend.files.upload', compact('categories'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'category_id' => 'required|exists:categories,id',
            'game' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:50',
            'original_author' => 'nullable|string|max:255',
            'file' => 'required|file|max:' . (config('app.max_upload_size', 500) * 1024),
            'screenshots.*' => 'nullable|image|max:10240',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:50',
        ]);
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
        return redirect()->route('files.show', $file)
            ->with('success', __('messages.file_uploaded_success'));
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
