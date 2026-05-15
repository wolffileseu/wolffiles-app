@extends('etui.layouts.app')

@section('title', $mod->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center gap-3 mb-1">
        <span class="px-2 py-0.5 bg-gray-800 text-amber-400 font-mono text-sm rounded">{{ $mod->slug }}</span>
        <h1 class="text-2xl font-bold text-gray-100">{{ $mod->name }}</h1>
    </div>
    @if ($mod->description)
        <p class="text-gray-400 mb-6">{{ $mod->description }}</p>
    @endif

    <div class="text-sm text-gray-500 mb-3">{{ $files->count() }} stock files</div>

    <div class="bg-gray-800 border border-gray-700 rounded divide-y divide-gray-700">
        @foreach ($files as $file)
            <div class="flex items-center px-4 py-2 hover:bg-gray-750">
                <a href="{{ route('etui.file', [$mod->slug, $file->name]) }}"
                   class="flex-1 font-mono text-sm text-amber-300 hover:text-amber-100">
                    {{ $file->name }}
                </a>
                @if ($file->parse_status === 'clean')
                    <span class="text-xs text-emerald-400">✓ clean</span>
                @elseif ($file->parse_status === 'errors')
                    <span class="text-xs text-red-400">✗ {{ $file->parse_error_count }} errors</span>
                @else
                    <span class="text-xs text-gray-500">{{ $file->parse_status }}</span>
                @endif
            </div>
        @endforeach
        @if ($files->isEmpty())
            <div class="px-4 py-6 text-center text-gray-500 text-sm">
                No stock files imported yet. Run <code class="font-mono text-gray-400">php artisan db:seed --class=EtuiFileFromFixturesSeeder</code>.
            </div>
        @endif
    </div>
</div>
@endsection
