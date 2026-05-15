<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Etui\AstSerializer;
use App\Etui\MenuFile;
use App\Http\Controllers\Controller;
use App\Models\Etui\EtuiFile;
use App\Models\Etui\EtuiMod;
use App\Models\Etui\EtuiProject;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class EtuiPublicController extends Controller
{
    public function index()
    {
        $mods = EtuiMod::active()
            ->withCount(['files', 'projects'])
            ->orderBy('order')
            ->get();

        $featured = EtuiProject::public()
            ->with(['user:id,name', 'mod:id,slug,name'])
            ->orderByDesc('last_saved_at')
            ->limit(8)
            ->get();

        return view('etui.public.index', compact('mods', 'featured'));
    }

    public function mod(string $slug)
    {
        $mod = EtuiMod::where('slug', $slug)->active()->firstOrFail();
        $files = $mod->files()
            ->stock()
            ->orderBy('name')
            ->get(['id', 'name', 'parse_status', 'parse_error_count']);

        return view('etui.public.mod', compact('mod', 'files'));
    }

    public function file(string $slug, string $name)
    {
        $mod = EtuiMod::where('slug', $slug)->active()->firstOrFail();
        $file = $mod->files()
            ->stock()
            ->where('name', $name)
            ->firstOrFail();

        // AST is the expensive part — cache by file id so user-uploads bust
        // automatically on save (id stays, content changes invalidate the
        // updated_at column but we key on id; sleeps with updated_at in key).
        $cacheKey = "etui.ast.file.{$file->id}.{$file->updated_at?->timestamp}";
        $ast = Cache::remember($cacheKey, 3600, function () use ($file, $mod): array {
            return AstSerializer::serialize(
                MenuFile::parse((string) $file->content, $mod->profile),
            );
        });

        return view('etui.public.file', [
            'mod' => $mod,
            'file' => $file,
            'ast' => $ast,
        ]);
    }

    public function share(string $token)
    {
        $project = EtuiProject::where('share_token', $token)->firstOrFail();
        // share_token alone doesn't unlock a private project — the owner
        // must have actively flipped is_public for the link to work.
        if (! $project->is_public) {
            abort(Response::HTTP_NOT_FOUND);
        }
        $project->load(['user:id,name', 'mod:id,slug,name,parser_profile']);

        $cacheKey = "etui.ast.project.{$project->id}.{$project->last_saved_at?->timestamp}";
        $ast = Cache::remember($cacheKey, 600, function () use ($project): array {
            return AstSerializer::serialize(
                MenuFile::parse((string) $project->content, $project->mod->profile),
            );
        });

        return view('etui.public.share', compact('project', 'ast'));
    }
}
