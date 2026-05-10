<?php

namespace App\Http\Controllers;

use App\Services\MultipartUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Multipart upload API for browser-direct uploads to S3.
 * Used by Uppy.io frontend for files > 100MB.
 */
class MultipartUploadController extends Controller
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024 * 1024; // 5 GB
    private const PART_SIZE = 100 * 1024 * 1024; // 100 MB per part
    private const MAX_PARTS = 10000; // S3 limit

    private const ALLOWED_TARGETS = ['files', 'demos', 'fastdl'];

    public function __construct(
        private MultipartUploadService $service
    ) {}

    /**
     * POST /upload-api/init
     * Initiates a new multipart upload.
     */
    public function init(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string|max:255',
            'size' => 'required|integer|min:1|max:' . self::MAX_FILE_SIZE,
            'content_type' => 'nullable|string|max:200',
            'target' => 'required|string|in:' . implode(',', self::ALLOWED_TARGETS),
            'file_hash' => 'nullable|string|size:64',  // SHA-256 hex
        ]);

        // Duplicate-Check (nur bei target=files, demos)
        if (!empty($data['file_hash']) && in_array($data['target'], ['files', 'demos'])) {
            $existing = match ($data['target']) {
                'files' => \App\Models\File::where('file_hash', $data['file_hash'])->first(),
                'demos' => \App\Models\Demo::where('file_hash', $data['file_hash'])->first(),
                default => null,
            };
            if ($existing) {
                return response()->json([
                    'error' => 'duplicate',
                    'message' => 'This file has already been uploaded.',
                    'existing_id' => $existing->id,
                    'existing_title' => $existing->title,
                ], 409);
            }
        }

        $key = $this->service->generateKey($data['target'], $data['filename']);
        $init = $this->service->initiate($key, $data['content_type'] ?? null);

        // Track upload session in cache for security checks (5h TTL)
        $sessionKey = "mpu:{$init['uploadId']}";
        Cache::put($sessionKey, [
            'user_id' => $request->user()->id,
            'target' => $data['target'],
            'key' => $key,
            'size' => $data['size'],
            'filename' => $data['filename'],
            'content_type' => $data['content_type'] ?? null,
            'file_hash' => $data['file_hash'] ?? null,
            'created_at' => now()->toIso8601String(),
        ], now()->addHours(5));

        Log::info('Multipart upload initiated', [
            'user_id' => $request->user()->id,
            'target' => $data['target'],
            'filename' => $data['filename'],
            'size' => $data['size'],
            'key' => $key,
        ]);

        return response()->json([
            'uploadId' => $init['uploadId'],
            'key' => $init['key'],
        ]);
    }

    /**
     * POST /upload-api/sign
     * Returns a presigned URL for uploading one specific part.
     */
    public function sign(Request $request)
    {
        $data = $request->validate([
            'uploadId' => 'required|string',
            'key' => 'required|string',
            'partNumber' => 'required|integer|min:1|max:' . self::MAX_PARTS,
        ]);

        // Verify the upload belongs to this user
        $session = Cache::get("mpu:{$data['uploadId']}");
        if (!$session || $session['user_id'] !== $request->user()->id) {
            return response()->json(['error' => 'Invalid or expired upload session'], 403);
        }
        if ($session['key'] !== $data['key']) {
            return response()->json(['error' => 'Key mismatch'], 403);
        }

        $url = $this->service->signPart(
            $data['uploadId'],
            $data['key'],
            $data['partNumber']
        );

        return response()->json(['url' => $url]);
    }

    /**
     * POST /upload-api/complete
     * Finalizes the upload after all parts are uploaded.
     */
    public function complete(Request $request)
    {
        $data = $request->validate([
            'uploadId' => 'required|string',
            'key' => 'required|string',
            'parts' => 'required|array|min:1',
            'parts.*.PartNumber' => 'required|integer|min:1',
            'parts.*.ETag' => 'required|string',
        ]);

        $session = Cache::get("mpu:{$data['uploadId']}");
        if (!$session || $session['user_id'] !== $request->user()->id) {
            return response()->json(['error' => 'Invalid or expired upload session'], 403);
        }

        $location = $this->service->complete(
            $data['uploadId'],
            $data['key'],
            $data['parts']
        );

        // Cleanup session
        Cache::forget("mpu:{$data['uploadId']}");

        Log::info('Multipart upload completed', [
            'user_id' => $request->user()->id,
            'key' => $data['key'],
            'parts' => count($data['parts']),
        ]);

        return response()->json([
            'success' => true,
            'key' => $data['key'],
            'url' => $location,
            'target' => $session['target'],
            'filename' => $session['filename'],
            'size' => $session['size'],
            'file_hash' => $session['file_hash'] ?? null,
            'content_type' => $session['content_type'] ?? null,
        ]);
    }

    /**
     * POST /upload-api/abort
     * Aborts an upload, cleans up parts in S3.
     */
    public function abort(Request $request)
    {
        $data = $request->validate([
            'uploadId' => 'required|string',
            'key' => 'required|string',
        ]);

        $session = Cache::get("mpu:{$data['uploadId']}");
        if (!$session || $session['user_id'] !== $request->user()->id) {
            return response()->json(['error' => 'Invalid session'], 403);
        }

        $this->service->abort($data['uploadId'], $data['key']);
        Cache::forget("mpu:{$data['uploadId']}");

        Log::info('Multipart upload aborted', [
            'user_id' => $request->user()->id,
            'key' => $data['key'],
        ]);

        return response()->json(['success' => true]);
    }
}
