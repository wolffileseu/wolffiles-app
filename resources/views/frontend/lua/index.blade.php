<x-layouts.app title="LUA Scripts">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-6">LUA Script Database</h1>

        {{-- Search & Filter --}}
        <form method="GET" class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search scripts..."
                       class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                <select name="mod" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">All Mods</option>
                    @foreach(['etpub' => 'ETPub', 'silent' => 'Silent', 'nitmod' => 'N!tmod', 'legacy' => 'ET:Legacy', 'jaymod' => 'Jaymod'] as $key => $label)
                        <option value="{{ $key }}" {{ request('mod') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Search</button>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($scripts as $script)
                <a href="{{ route('lua.show', $script) }}" class="block bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-amber-600 transition-colors">
                    <h3 class="text-lg font-semibold text-white mb-2">{{ $script->title }}</h3>
                    <p class="text-gray-400 text-sm mb-4 line-clamp-3">{{ Str::limit(strip_tags($script->description), 150) }}</p>

                    @if($script->compatible_mods)
                        <div class="flex flex-wrap gap-1 mb-3">
                            @foreach($script->compatible_mods as $mod)
                                <span class="bg-gray-700 text-gray-300 px-2 py-0.5 rounded text-xs">{{ $mod }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-between text-xs text-gray-500">
                        <span>v{{ $script->version ?? '1.0' }}</span>
                        <span>↓ {{ $script->download_count }}</span>
                        <span>by {{ $script->user?->name }}</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center text-gray-400 py-12">No LUA scripts found.</div>
            @endforelse
        </div>

        <div class="mt-8">{{ $scripts->links() }}</div>
    </div>
</x-layouts.app>
