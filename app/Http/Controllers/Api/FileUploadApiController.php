<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\FileScreenshot;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\AutoApproveService;
use App\Jobs\AnalyzeUploadedFile;
use App\Jobs\ScanFileForViruses;

class FileUploadApiController extends Controller
{
    /**
     * POST /api/v1/files
     * Multipart upload from Wolffiles Uploader desktop app.
     *
     * Accepts:
     *   file            (required, max 500 MB)
     *   title           (required, string)
     *   description     (optional, string)
     *   category_id     (required, exists)
     *   version         (optional, string)
     *   original_author (optional, string) [legacy: "author"]
     *   game            (optional, "auto" | "et" | "rtcw")
     *   tags[]          (optional, array of tag names)
     *   screenshot      (optional, single image) [legacy, single]
     *   screenshots[]   (optional, array of images, max 10)
     */
    public function store(Request $request)
    {
        // Backward-compat: app sends "author", DB-column is "original_author"
        if ($request->filled('author') && !$request->filled('original_author')) {
            $request->merge(['original_author' => $request->input('author')]);
        }

        $isMultipart = $request->filled('file_s3_key');

        $rules = [
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string|max:5000',
            'category_id'       => 'required|integer|exists:categories,id',
            'version'           => 'nullable|string|max:50',
            'original_author'   => 'nullable|string|max:255',
            'game'              => 'nullable|string|in:auto,et,rtcw',
            'tags'              => 'nullable|array|max:30',
            'tags.*'            => 'string|max:80',
            'screenshot'        => 'nullable|image|max:10240',           // 10 MB (legacy single)
            'screenshots'       => 'nullable|array|max:10',
            'screenshots.*'     => 'image|mimes:jpg,jpeg,png,webp|max:10240',
        ];

        if ($isMultipart) {
            // App used multipart upload — file is already in S3
            $rules['file_s3_key']       = 'required|string|max:500';
            $rules['file_filename']     = 'required|string|max:255';
            $rules['file_size']         = 'required|integer|min:1';
            $rules['file_hash']         = 'nullable|string|size:64';
            $rules['file_content_type'] = 'nullable|string|max:200';
        } else {
            // Classic upload — file is in the request body
            $rules['file'] = 'required|file|max:512000';                 // 500 MB
        }

        $validated = $request->validate($rules);

        $user = $request->user();

        // ── Game mapping ─────────────────────────────────────────────────────
        // App sends "auto" / "et" / "rtcw" — DB stores "ET" / "RtCW" or null (auto-detect later)
        $game = match ($request->input('game', 'auto')) {
            'et'   => 'ET',
            'rtcw' => 'RtCW',
            default => null,
        };

        if ($isMultipart) {
            // ── Multipart path: file is already in S3 ───────────────────────
            $storedPath  = $validated['file_s3_key'];
            $fileName    = $validated['file_filename'];
            $fileSize    = (int) $validated['file_size'];
            $fileHash    = $validated['file_hash'] ?? null;
            $mimeType    = $validated['file_content_type'] ?? 'application/octet-stream';
            $extension   = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            Log::info('[FileUploadApi] incoming upload (multipart)', [
                'user_id'     => $user->id,
                'title'       => $validated['title'],
                'file_size'   => $fileSize,
                'file_hash'   => $fileHash,
                's3_key'      => $storedPath,
                'game'        => $request->input('game', 'auto'),
                'tags_count'  => is_array($request->input('tags')) ? count($request->input('tags')) : 0,
                'screenshots' => $request->hasFile('screenshots')
                                    ? count($request->file('screenshots'))
                                    : ($request->hasFile('screenshot') ? 1 : 0),
            ]);

            // ── Duplicate check via hash (if provided) ──────────────────────
            if ($fileHash) {
                $duplicate = File::where('file_hash', $fileHash)->whereNull('deleted_at')->first();
                if ($duplicate) {
                    return response()->json([
                        'error'   => 'duplicate',
                        'message' => 'A file with the same content already exists',
                        'existing' => [
                            'id'    => $duplicate->id,
                            'slug'  => $duplicate->slug,
                            'title' => $duplicate->title,
                        ],
                    ], 409);
                }
            }
        } else {
            // ── Classic path: file is in request body ───────────────────────
            $uploadedFile = $request->file('file');

            Log::info('[FileUploadApi] incoming upload (classic)', [
                'user_id'     => $user->id,
                'title'       => $validated['title'],
                'file_size'   => $uploadedFile->getSize(),
                'game'        => $request->input('game', 'auto'),
                'tags_count'  => is_array($request->input('tags')) ? count($request->input('tags')) : 0,
                'screenshots' => $request->hasFile('screenshots')
                                    ? count($request->file('screenshots'))
                                    : ($request->hasFile('screenshot') ? 1 : 0),
            ]);

            $storedPath = $uploadedFile->store('files/' . date('Y/m'), 's3');
            $fileName   = $uploadedFile->getClientOriginalName();
            $fileSize   = $uploadedFile->getSize();
            $fileHash   = null; // will be computed by AnalyzeUploadedFile job
            $mimeType   = $uploadedFile->getMimeType();
            $extension  = strtolower($uploadedFile->getClientOriginalExtension());
        }

        // ── Create DB record (both paths) ────────────────────────────────────
        $file = File::create([
            'user_id'         => $user->id,
            'category_id'     => $validated['category_id'],
            'title'           => $validated['title'],
            'slug'            => Str::slug($validated['title']) . '-' . Str::random(6),
            'description'     => $validated['description'] ?? null,
            'version'         => $validated['version'] ?? null,
            'original_author' => $validated['original_author'] ?? null,
            'game'            => $game,
            'file_path'       => $storedPath,
            'file_name'       => $fileName,
            'file_extension'  => $extension,
            'file_size'       => $fileSize,
            'file_hash'       => $fileHash,
            'mime_type'       => $mimeType,
            'status'          => 'pending',
        ]);

        // Auto-approve for trusted users (same logic as web upload)
        AutoApproveService::processUpload($file);

        // Dispatch background jobs (same as web upload)
        AnalyzeUploadedFile::dispatch($file);
        ScanFileForViruses::dispatch($file);

        // ── Tags: firstOrCreate by slug, then sync ───────────────────────────
        if (!empty($validated['tags'])) {
            $tagIds = [];
            foreach ($validated['tags'] as $tagName) {
                $tagName = trim($tagName);
                if ($tagName === '') continue;

                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($tagName)],
                    ['name' => $tagName]
                );
                $tagIds[] = $tag->id;
            }
            if (!empty($tagIds)) {
                $file->tags()->sync($tagIds);
                Log::info('[FileUploadApi] attached tags', [
                    'file_id' => $file->id,
                    'tags'    => $tagIds,
                ]);
            }
        }

        // ── Screenshots: legacy single + new multi (max 10 combined) ─────────
        $screenshotPaths = [];

        // Legacy: single "screenshot" field (current app version)
        if ($request->hasFile('screenshot')) {
            $screenshotPaths[] = $request->file('screenshot');
        }

        // New: "screenshots[]" array (upcoming app version)
        if ($request->hasFile('screenshots')) {
            foreach ($request->file('screenshots') as $shot) {
                if (count($screenshotPaths) >= 10) break;
                $screenshotPaths[] = $shot;
            }
        }

        foreach ($screenshotPaths as $index => $shot) {
            $shotPath = $shot->store('screenshots/' . $file->id, 's3');

            FileScreenshot::create([
                'file_id'       => $file->id,
                'path'          => $shotPath,
                'original_name' => $shot->getClientOriginalName(),
                'source'        => 'uploaded',
                'sort_order'    => $index,
                'is_primary'    => $index === 0,
            ]);
        }

        if (!empty($screenshotPaths)) {
            Log::info('[FileUploadApi] saved screenshots', [
                'file_id' => $file->id,
                'count'   => count($screenshotPaths),
            ]);
        }

        return response()->json([
            'data' => [
                'id'              => $file->id,
                'title'           => $file->title,
                'status'          => $file->status,
                'game'            => $file->game,
                'url'             => route('files.show', $file->slug),
                'tags_count'      => $file->tags()->count(),
                'screenshots'     => $file->screenshots()->count(),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/files/my
     * Returns the authenticated user's uploaded files.
     */
    public function myFiles(Request $request)
    {
        $files = $request->user()
            ->files()
            ->with(['category', 'tags', 'primaryScreenshot'])
            ->latest()
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'data'         => $files->items(),
            'total'        => $files->total(),
            'per_page'     => $files->perPage(),
            'current_page' => $files->currentPage(),
        ]);
    }
}
