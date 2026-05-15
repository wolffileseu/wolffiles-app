@extends('etui.layouts.app')

@section('title', $file->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center gap-3 mb-4 text-sm">
        <a href="{{ route('etui.mod', $mod->slug) }}" class="text-gray-400 hover:text-amber-300">&larr; {{ $mod->slug }}</a>
        <span class="text-gray-600">/</span>
        <h1 class="font-mono text-gray-100">{{ $file->name }}</h1>
        @auth
            <form method="POST" action="{{ route('etui.projects.store') }}" class="ml-auto">
                @csrf
                <input type="hidden" name="mod_id" value="{{ $mod->id }}">
                <input type="hidden" name="name" value="Fork of {{ $file->name }}">
                <input type="hidden" name="content" value="{{ $file->content }}">
                <button class="text-xs px-3 py-1 bg-amber-600 hover:bg-amber-500 text-gray-900 rounded font-semibold">
                    Fork to project
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
            <div class="text-xs text-gray-500 mt-2">
                @if (empty($ast['errors']))
                    <span class="text-emerald-400">✓ parsed cleanly</span> — {{ count($ast['menus']) }} menu(s)
                @else
                    <span class="text-red-400">✗ {{ count($ast['errors']) }} errors</span> — first: L{{ $ast['errors'][0]['line'] }}: {{ Str::limit($ast['errors'][0]['message'], 80) }}
                @endif
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-gray-500 mb-2">Source</div>
            <pre class="bg-gray-950 border border-gray-700 rounded p-3 text-xs text-gray-300 overflow-auto max-h-[80vh] whitespace-pre">{{ $file->content }}</pre>
        </div>
    </div>
</div>

@push('scripts')
    @vite('resources/js/etui/renderer.js')
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            const ast = {!! json_encode($ast) !!};
            window.renderMenu(ast, document.getElementById('etui-preview'));
        });
    </script>
@endpush
@endsection
