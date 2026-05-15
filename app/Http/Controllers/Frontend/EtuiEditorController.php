<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Etui\EtuiMod;
use App\Models\Etui\EtuiProject;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

final class EtuiEditorController extends Controller
{
    use AuthorizesRequests;

    public function edit(Request $request, ?int $project = null)
    {
        $loaded = null;
        if ($project !== null) {
            $loaded = EtuiProject::findOrFail($project);
            $this->authorize('update', $loaded);
            $loaded->load('mod:id,slug,name,parser_profile');
        }

        $mods = EtuiMod::active()->orderBy('order')->get(['id', 'slug', 'name']);
        // Default mod for new projects: query param wins, else etmain.
        $defaultModSlug = $request->query('mod', 'etmain');

        return view('etui.editor.index', [
            'project' => $loaded,
            'mods' => $mods,
            'defaultModSlug' => $defaultModSlug,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mod_id' => ['required', 'integer', 'exists:etui_mods,id'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:200000'],
        ]);

        $project = EtuiProject::create([
            'user_id' => $request->user()->id,
            'mod_id' => $validated['mod_id'],
            'name' => $validated['name'],
            'content' => $validated['content'],
            'last_saved_at' => now(),
        ]);
        $project->snapshot();
        $project->reparse();

        return redirect()->route('etui.edit', ['project' => $project->id]);
    }

    public function update(Request $request, int $project)
    {
        $model = EtuiProject::findOrFail($project);
        $this->authorize('update', $model);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string', 'max:200000'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['content']) && $validated['content'] !== $model->content) {
            $model->snapshot();
        }

        $model->fill($validated);
        if ($model->isDirty('content')) {
            $model->last_saved_at = now();
        }
        $model->save();

        if ($model->wasChanged('content')) {
            $model->reparse();
        }

        return back()->with('status', 'Project updated');
    }

    public function destroy(int $project)
    {
        $model = EtuiProject::findOrFail($project);
        $this->authorize('delete', $model);
        $model->delete();

        return redirect()->route('etui.index')->with('status', 'Project deleted');
    }
}
