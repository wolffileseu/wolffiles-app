@extends('etui.layouts.app')

@section('title', $project ? 'Edit · '.$project->name : 'New Project')

@push('head')
    @vite(['resources/js/etui/editor.js'])
@endpush

@section('content')
@php
    // Blade @php blocks don't tolerate flexible-heredoc closers cleanly —
    // Blade's tokenizer mis-reads the closing identifier as a directive
    // boundary. Plain double-quoted string with explicit \n is robust.
    $modSlug = $project?->mod?->slug ?? $defaultModSlug;
    $defaultStarter = "menuDef {\n    name        \"myMenu\"\n    fullscreen  0\n    rect        16 16 608 448\n    style       WINDOW_STYLE_FILLED\n    visible     0\n\n    itemDef {\n        name        \"title\"\n        rect        18 28 572 14\n        text        \"Hello World\"\n        textscale   .35\n        forecolor   1 1 1 1\n        visible     1\n    }\n}";
    $initialContent = $project?->content ?? $defaultStarter;
@endphp

<div x-data="{
    projectId: {{ $project?->id ?? 'null' }},
    name: @js($project?->name ?? 'Untitled'),
    isPublic: {{ $project?->is_public ? 'true' : 'false' }},
    modId: {{ $project?->mod_id ?? "''" }},
    shareUrl: @js($project ? url($project->share_url) : null),
    saving: false,
    async save() {
        this.saving = true;
        try {
            if (this.projectId) {
                await axios.put('/etui/projects/' + this.projectId, {
                    name: this.name,
                    content: window.etuiEditor.getContent(),
                    is_public: this.isPublic,
                });
            } else {
                const res = await axios.post('/etui/projects', {
                    mod_id: this.modId || {{ $mods->firstWhere('slug', $defaultModSlug)?->id ?? 'null' }},
                    name: this.name,
                    content: window.etuiEditor.getContent(),
                });
                // The store action redirects — follow the Location header.
                window.location.assign(res.request.responseURL);
            }
        } finally {
            this.saving = false;
        }
    },
}">
    <div class="bg-gray-950 border-b border-gray-800 px-4 py-2 flex items-center gap-3 text-sm">
        <input x-model="name" placeholder="Project name"
               class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-gray-100 w-64 focus:outline-none focus:border-amber-500">
        @if (!$project)
            <select x-model="modId" class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-gray-100">
                @foreach ($mods as $m)
                    <option value="{{ $m->id }}" @selected($m->slug === $defaultModSlug)>{{ $m->name }}</option>
                @endforeach
            </select>
        @else
            <span class="px-2 py-0.5 bg-gray-800 text-amber-400 font-mono text-xs rounded">{{ $project->mod->slug }}</span>
        @endif

        <label class="flex items-center gap-1 text-gray-400 text-xs cursor-pointer">
            <input type="checkbox" x-model="isPublic"> public
        </label>

        <div class="flex-1"></div>

        <template x-if="shareUrl">
            <button @click="navigator.clipboard.writeText(shareUrl); $event.target.textContent='copied!'"
                    class="text-xs px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-100 rounded">
                Copy share link
            </button>
        </template>

        <button @click="save()" :disabled="saving"
                class="text-xs px-4 py-1 bg-amber-600 hover:bg-amber-500 disabled:opacity-50 text-gray-900 rounded font-semibold">
            <span x-text="saving ? 'Saving...' : 'Save'"></span>
        </button>
    </div>

    <div class="etui-editor">
        <div class="editor-col">
            <div id="etui-editor"></div>
        </div>
        <div class="preview-col">
            <div id="etui-preview" class="etui-canvas"></div>
        </div>
    </div>

    <div class="etui-status-bar">
        <span id="etui-status" class="status">parsing…</span>
        <div class="flex-1"></div>
        <span id="etui-save-status" class="status"></span>
    </div>
</div>

<script type="application/json" id="etui-opts">{!! json_encode(['projectId' => $project?->id, 'modSlug' => $modSlug, 'initialContent' => $initialContent]) !!}</script>
@endsection
