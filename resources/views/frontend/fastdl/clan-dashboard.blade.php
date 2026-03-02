<x-layouts.app :title="$clan->name . ' — Fast Download'">
<div class="max-w-5xl mx-auto px-4 py-8">

    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white">🖥️ {{ $clan->name }}</h1>
            <p class="text-gray-400 mt-1">Fast Download Management — {{ $game->name }}</p>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-400">Server Config:</div>
            <code class="text-amber-400 text-xs bg-gray-800 px-3 py-1 rounded">sv_wwwBaseURL "https://dl.wolffiles.eu/{{ $clan->slug }}"</code>
        </div>
    </div>

    <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6 mb-8">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-lg font-semibold text-white">💾 Storage</h2>
            <span class="text-gray-400 text-sm">{{ round($storageUsed / 1024 / 1024, 1) }} MB / {{ $clan->storage_limit_mb }} MB</span>
        </div>
        <div style="width:100%;background:#374151;border-radius:9999px;height:20px;overflow:hidden;">
            <div style="width:{{ max($storagePercent, 1) }}%;height:100%;border-radius:9999px;background:linear-gradient(to right,#f59e0b,#ea580c);"></div>
        </div>
        <div class="text-right text-xs text-gray-500 mt-1">{{ $storagePercent }}% used</div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">

            @if($clan->include_base)
            <div class="bg-green-900/20 border border-green-700/50 rounded-xl p-5">
                <h3 class="text-white font-semibold mb-2">✅ {{ $game->base_directory }}/ — Auto-included</h3>
                <p class="text-gray-400 text-sm">All maps are automatically available in this directory.</p>
                <div class="mt-2 text-amber-400 text-sm">
                    <code>dl.wolffiles.eu/{{ $clan->slug }}/{{ $game->base_directory }}/</code>
                </div>
            </div>
            @endif

            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">📦 Select Mod Directories</h3>
                <form action="{{ route('clan.fastdl.directories') }}" method="POST">
                    @csrf
                    @if($availableDirs->count() > 0)
                    <div class="space-y-3">
                        @foreach($availableDirs as $dir)
                        <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-700 hover:border-amber-500 cursor-pointer transition">
                            <input type="checkbox" name="directories[]" value="{{ $dir->id }}"
                                {{ $selectedDirs->contains('id', $dir->id) ? 'checked' : '' }}
                                class="rounded border-gray-600 text-amber-500 focus:ring-amber-500">
                            <div>
                                <div class="text-white font-medium">{{ $dir->name }}</div>
                                <div class="text-gray-500 text-xs">{{ $dir->files()->where('is_active', true)->count() }} files</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    <button type="submit" class="mt-4 px-6 py-2 rounded-lg font-medium text-white" style="background:linear-gradient(to right,#f59e0b,#ea580c);">Save Selection</button>
                    @else
                    <p class="text-gray-500">No mod directories available yet.</p>
                    @endif
                </form>
            </div>

            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">📤 Upload Files</h3>
                <form action="{{ route('clan.fastdl.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="text-sm text-gray-400 block mb-1">Target Directory</label>
                            <select name="directory" class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-2 text-white">
                                <option value="{{ $game->base_directory }}">{{ $game->base_directory }}/</option>
                                @foreach($selectedDirs as $dir)
                                <option value="{{ $dir->slug }}">{{ $dir->slug }}/</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-400 block mb-1">PK3 File</label>
                            <input type="file" name="file" accept=".pk3,.zip" class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-2 text-white text-sm">
                        </div>
                    </div>
                    <button type="submit" class="px-6 py-2 rounded-lg font-medium text-white bg-blue-600 hover:bg-blue-500 transition">Upload</button>
                </form>
            </div>
        </div>

        <div>
            <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">📁 Your Files</h3>
                @forelse($ownFiles as $file)
                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-700/50' : '' }}">
                    <div>
                        <div class="text-white text-sm font-mono">{{ $file->filename }}</div>
                        <div class="text-gray-500 text-xs">{{ $file->directory }}/ — {{ round($file->file_size / 1024 / 1024, 1) }} MB</div>
                    </div>
                    <form action="{{ route('clan.fastdl.delete', $file) }}" method="POST" onsubmit="return confirm('Delete?')">
                        @csrf @method('DELETE')
                        <button class="text-red-400 hover:text-red-300 text-xs">✕</button>
                    </form>
                </div>
                @empty
                <p class="text-gray-500 text-sm">No files uploaded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="fixed bottom-4 right-4 bg-green-900/90 border border-green-700 text-green-300 px-6 py-3 rounded-xl shadow-lg" x-data x-init="setTimeout(() => $el.remove(), 3000)">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="fixed bottom-4 right-4 bg-red-900/90 border border-red-700 text-red-300 px-6 py-3 rounded-xl shadow-lg" x-data x-init="setTimeout(() => $el.remove(), 3000)">{{ session('error') }}</div>
    @endif
</div>
</x-layouts.app>
