<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FileUploadApiController extends Controller
{
    /**
     * POST /api/v1/files
     * Multipart upload from Wolffiles Uploader desktop app
     */
    public function store(Request $request)
    {
        $request->validate([
            'file'        => 'required|file|max:512000', // 500 MB max
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'required|integer|exists:categories,id',
            'version'     => 'nullable|string|max:50',
            'author'      => 'nullable|string|max:100',
            'screenshot'  => 'nullable|image|max:5120', // 5 MB
        ]);

        $uploadedFile = $request->file('file');
        $user = $request->user();

        // Store to S3
        $path = $uploadedFile->store(
            'files/' . date('Y/m'),
            's3'
        );

        // Screenshot
        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store(
                'screenshots/' . date('Y/m'),
                's3'
            );
        }

        // Create DB record
        $file = File::create([
            'user_id'       => $user->id,
            'category_id'   => $request->category_id,
            'title'         => $request->title,
            'slug'          => Str::slug($request->title) . '-' . Str::random(6),
            'description'   => $request->description,
            'version'       => $request->version,
            'author'        => $request->author ?? $user->name,
            'file_path'     => $path,
            'file_name'     => $uploadedFile->getClientOriginalName(),
            'file_size'     => $uploadedFile->getSize(),
            'file_type'     => strtolower($uploadedFile->getClientOriginalExtension()),
            'screenshot'    => $screenshotPath,
            'status'        => 'pending', // admin reviews before publish
            'upload_source' => 'desktop_app',
        ]);

        return response()->json([
            'data' => [
                'id'     => $file->id,
                'title'  => $file->title,
                'status' => $file->status,
                'url'    => route('files.show', $file->slug),
            ],
        ], 201);
    }

    /**
     * GET /api/v1/files/my
     * Returns the authenticated user's uploaded files
     */
    public function myFiles(Request $request)
    {
        $files = $request->user()
            ->files()
            ->with('category')
            ->latest()
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'data' => $files->items(),
            'total' => $files->total(),
            'per_page' => $files->perPage(),
            'current_page' => $files->currentPage(),
        ]);
    }
}
