@extends('etui.layouts.app')

@section('title', 'Mods')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-100 mb-1">ET Menu Builder</h1>
    <p class="text-gray-400 mb-8">Browse stock UI files from 9 Wolfenstein mods, or build your own menu live in the editor.</p>

    <h2 class="text-sm uppercase tracking-wider text-gray-500 mb-3">Mods</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-12">
        @foreach ($mods as $mod)
            <a href="{{ route('etui.mod', $mod->slug) }}"
               class="block bg-gray-800 hover:bg-gray-750 border border-gray-700 rounded p-4 transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="font-mono text-amber-400">{{ $mod->slug }}</span>
                    <span class="text-xs text-gray-500">order {{ $mod->order }}</span>
                </div>
                <div class="text-gray-100 font-semibold">{{ $mod->name }}</div>
                <div class="text-xs text-gray-500 mt-2">
                    {{ $mod->files_count }} stock files · {{ $mod->projects_count }} projects
                </div>
            </a>
        @endforeach
    </div>

    @if ($featured->isNotEmpty())
        <h2 class="text-sm uppercase tracking-wider text-gray-500 mb-3">Featured projects</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ($featured as $project)
                <a href="{{ route('etui.share', $project->share_token) }}"
                   class="block bg-gray-800 hover:bg-gray-750 border border-gray-700 rounded p-3">
                    <div class="text-xs text-amber-400 font-mono mb-1">{{ $project->mod->slug }}</div>
                    <div class="text-sm font-semibold text-gray-100">{{ $project->name }}</div>
                    <div class="text-xs text-gray-500 mt-2">by {{ $project->user->name }}</div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
