<?php

namespace App\Http\Controllers\Admin;

use InvalidArgumentException;
use Throwable;
use Log;
use App\Http\Controllers\Controller;
use App\Models\WikiMedia;
use App\Services\Wiki\WikiMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class WikiMediaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                $u = $request->user();
                abort_unless($u && $u->hasAnyRole(['admin', 'moderator']), 403);
                return $next($request);
            }),
        ];
    }

    public function __construct(private WikiMediaService $svc) {}

    /** POST /admin/wiki-media/upload  — Datei + (optional) article_id */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file'       => ['required', 'file', 'image', 'max:' . (WikiMediaService::MAX_BYTES / 1024)],
            'article_id' => ['nullable', 'integer', 'exists:wiki_articles,id'],
        ]);

        try {
            $media = $this->svc->store(
                $request->file('file'),
                $request->user()->id,
                $request->integer('article_id') ?: null,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('WikiMedia upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Upload fehlgeschlagen'], 500);
        }

        return response()->json($this->toJson($media), 201);
    }

    /** GET /admin/wiki-media/pool?q=&page= — paginierter Pool aller Wiki-Bilder */
    public function pool(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = WikiMedia::query()
            ->where('type', 'image')
            ->orderByDesc('id');

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('filename', 'like', $like)
                  ->orWhere('caption', 'like', $like);
            });
        }

        $paginator = $query->paginate(24)->withQueryString();

        return response()->json([
            'data'         => collect($paginator->items())->map(fn ($m) => $this->toJson($m))->all(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'total'        => $paginator->total(),
        ]);
    }

    /** DELETE /admin/wiki-media/{media} — entfernt DB-Row + S3-Objekt */
    public function destroy(WikiMedia $media): JsonResponse
    {
        try {
            Storage::disk(WikiMediaService::DISK)->delete($media->path);
        } catch (Throwable $e) {
            Log::warning('S3-Delete fuer WikiMedia ' . $media->id . ' fehlgeschlagen: ' . $e->getMessage());
        }
        $media->delete();
        return response()->json(['deleted' => true]);
    }

    private function toJson(WikiMedia $m): array
    {
        return [
            'id'        => $m->id,
            'filename'  => $m->filename,
            'path'      => $m->path,
            'url'       => $m->url,
            'mime_type' => $m->mime_type,
            'file_size' => $m->file_size,
            'size_h'    => $m->file_size_formatted,
            'caption'   => $m->caption,
            'created'   => $m->created_at?->toIso8601String(),
            // fertiges Wikitext-Snippet (Default thumb|400px)
            'wikitext'  => '[[File:' . $m->filename . '|thumb|400px]]',
        ];
    }
}
