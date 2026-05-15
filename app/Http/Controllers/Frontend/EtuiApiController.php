<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Etui\AstSerializer;
use App\Etui\MenuFile;
use App\Http\Controllers\Controller;
use App\Models\Etui\EtuiMod;
use App\Models\Etui\EtuiProject;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints for the live editor. Hosted under web.php
 * (not api.php) so session + CSRF flow through unchanged — the editor
 * UI is same-origin, no token shenanigans needed.
 */
final class EtuiApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * POST /api/etui/parse
     * Public, rate-limited via route middleware. The live linter
     * + renderer call this every ~300ms of inactivity.
     */
    public function parse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => ['required', 'string', 'max:200000'],
            'mod' => ['nullable', 'string', 'max:64'],
        ]);

        $slug = $validated['mod'] ?? 'etmain';

        // DB-backed mod wins over ProfileRegistry slug — admins can register
        // user-mods that the static registry doesn't know yet.
        $mod = EtuiMod::where('slug', $slug)->first();
        try {
            $result = $mod
                ? MenuFile::parse($validated['source'], $mod->profile)
                : MenuFile::parse($validated['source'], $slug);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(AstSerializer::serialize($result));
    }

    /**
     * POST /api/etui/autosave
     * Owner-only via Policy. Snapshots the previous content as a revision
     * before persisting the new content so history never loses a version.
     */
    public function autosave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:etui_projects,id'],
            'content' => ['required', 'string', 'max:200000'],
        ]);

        $project = EtuiProject::findOrFail($validated['project_id']);
        $this->authorize('update', $project);

        // Only snapshot when content actually changed — otherwise a tab left
        // open in the background would still tick autosave-revisions every
        // few minutes without anyone touching the editor.
        if ($project->content !== $validated['content']) {
            $project->snapshot();
            $project->update([
                'content' => $validated['content'],
                'last_saved_at' => now(),
            ]);
            $project->reparse();
        }

        return response()->json([
            'saved_at' => $project->last_saved_at?->toIso8601String(),
            'parse_status' => $project->parse_status,
            'parse_error_count' => $project->parse_error_count,
        ]);
    }
}
