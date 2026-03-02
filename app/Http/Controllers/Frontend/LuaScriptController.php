<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\LuaScript;
use App\Models\Download;
use App\Models\Category;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Storage;

class LuaScriptController extends Controller
{
    public function index(Request $request)
    {
        $query = LuaScript::where('status', 'approved')->with(['category', 'user']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($mod = $request->input('mod')) {
            $query->whereJsonContains('compatible_mods', $mod);
        }

        if ($categoryId = $request->input('category')) {
            $query->where('category_id', $categoryId);
        }

        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'downloads' => $query->orderByDesc('download_count'),
            'name' => $query->orderBy('title'),
            default => $query->orderByDesc('created_at'),
        };

        $scripts = $query->paginate(24)->withQueryString();
        $categories = Category::where('type', 'lua')->where('is_active', true)->orderBy('sort_order')->get();

        return view('frontend.lua.index', compact('scripts', 'categories'));
    }

    public function show(LuaScript $luaScript)
    {
        abort_unless($luaScript->status === 'approved', 404);
        $luaScript->load(['user', 'category', 'comments.user']);
        $luaScript->increment('view_count');

        return view('frontend.lua.show', compact('luaScript'));
    }

    public function upload()
    {
        $categories = Category::where('type', 'lua')->where('is_active', true)->orderBy('sort_order')->get();
        return view('frontend.lua.upload', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'category_id' => 'required|exists:categories,id',
            'version' => 'nullable|string|max:50',
            'compatible_mods' => 'nullable|array',
            'compatible_mods.*' => 'string|max:50',
            'installation_guide' => 'nullable|string|max:5000',
            'min_lua_version' => 'nullable|string|max:20',
            'file' => 'required|file|max:10240',
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('lua-scripts/' . date('Y/m'), 's3');

        $luaScript = LuaScript::create([
            'title' => $request->title,
            'slug' => \Illuminate\Support\Str::slug($request->title),
            'description' => $request->description,
            'category_id' => $request->category_id,
            'user_id' => auth()->id(),
            'version' => $request->version,
            'compatible_mods' => $request->compatible_mods,
            'installation_guide' => $request->installation_guide,
            'min_lua_version' => $request->min_lua_version,
            'file_path' => $path,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_size' => $uploadedFile->getSize(),
            'status' => 'pending',
        ]);

        return redirect()->route('lua.index')
            ->with('success', __('messages.upload_review_info'));
        // Lua upload is logged via the File upload flow
    }

    public function download(LuaScript $luaScript)
    {
        abort_unless($luaScript->status === 'approved', 404);

        // Log download without foreign key constraint (lua_scripts != files table)
        try {
            \Illuminate\Support\Facades\DB::table('downloads')->insert([
                'file_id' => null,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Ignore download tracking errors
        }


        $luaScript->increment('download_count');

        // Force download with correct filename instead of redirect (fixes browser showing raw text)
        $disk = Storage::disk('s3');
        $path = $luaScript->file_path;

        if ($disk->exists($path)) {
            $fileName = $luaScript->file_name ?? ($luaScript->slug . '.lua');

            return $disk->download($path, $fileName, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        }

        // Fallback: signed URL with response-content-disposition
        $url = $disk->temporaryUrl($path, now()->addMinutes(30), [
            'ResponseContentDisposition' => 'attachment; filename="' . ($luaScript->file_name ?? $luaScript->slug . '.lua') . '"',
            'ResponseContentType' => 'application/octet-stream',
        ]);

        return redirect($url);
    }
}
