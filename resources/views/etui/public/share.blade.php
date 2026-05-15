@extends('etui.layouts.app')

@section('title', $project->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center gap-3 mb-4 text-sm">
        <span class="px-2 py-0.5 bg-gray-800 text-amber-400 font-mono text-xs rounded">{{ $project->mod->slug }}</span>
        <h1 class="text-gray-100 font-semibold">{{ $project->name }}</h1>
        <span class="text-gray-500">by {{ $project->user->name }}</span>
        @auth
            <form method="POST" action="{{ route('etui.projects.store') }}" class="ml-auto">
                @csrf
                <input type="hidden" name="mod_id" value="{{ $project->mod_id }}">
                <input type="hidden" name="name" value="Fork of {{ $project->name }}">
                <input type="hidden" name="content" value="{{ $project->content }}">
                <button class="text-xs px-3 py-1 bg-amber-600 hover:bg-amber-500 text-gray-900 rounded font-semibold">
                    Fork
                </button>
            </form>
        @endauth
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div>
            <div class="text-xs uppercase tracking-wider text-gray-500 mb-2">Preview</div>
            <div id="etui-preview-host" class="bg-gray-950 border border-gray-700 rounded p-2">
                <div id="etui-preview" class="etui-canvas mx-auto"></div>
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-gray-500 mb-2">Source</div>
            <pre class="bg-gray-950 border border-gray-700 rounded p-3 text-xs text-gray-300 overflow-auto max-h-[80vh] whitespace-pre">{{ $project->content }}</pre>
        </div>
    </div>
</div>

@push('scripts')
    @vite('resources/js/etui/renderer.js')
    <script>
        const ast = @json($ast);
        window.renderMenu(ast, document.getElementById('etui-preview'));
    </script>
@endpush
@endsection
