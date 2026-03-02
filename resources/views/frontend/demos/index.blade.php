<x-layouts.app title="Demos">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-white">🎬 Demo Database</h1>
            <div class="flex items-center space-x-4">
                @auth
                    <a href="{{ route('demos.upload') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Upload Demo
                    </a>
                @endauth
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search demos, maps, clans..."
                       class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                <select name="category" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <optgroup label="{{ $cat->name_translations[app()->getLocale()] ?? $cat->name }}">
                            @foreach($cat->children ?? collect() as $child)
                                <option value="{{ $child->slug }}" {{ request('category') == $child->slug ? 'selected' : '' }}>
                                    {{ $child->name_translations[app()->getLocale()] ?? $child->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <select name="game" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">All Games</option>
                    @foreach($games as $game)
                        <option value="{{ $game }}" {{ request('game') == $game ? 'selected' : '' }}>{{ $game }}</option>
                    @endforeach
                </select>
                <select name="mod" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">All Mods</option>
                    @foreach($mods as $mod)
                        <option value="{{ $mod }}" {{ request('mod') == $mod ? 'selected' : '' }}>{{ $mod }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-3">
                <select name="gametype" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">All Gametypes</option>
                    @foreach($gametypes as $gt)
                        <option value="{{ $gt }}" {{ request('gametype') == $gt ? 'selected' : '' }}>{{ $gt }}</option>
                    @endforeach
                </select>
                <select name="format" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">All Formats</option>
                    @foreach(['6on6' => '6on6', '5on5' => '5on5', '3on3' => '3on3', '2on2' => '2on2', '1on1' => '1on1', 'public' => 'Public'] as $k => $v)
                        <option value="{{ $k }}" {{ request('format') == $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
                <select name="sort" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                    <option value="downloads" {{ request('sort') == 'downloads' ? 'selected' : '' }}>Most Downloads</option>
                    <option value="match_date" {{ request('sort') == 'match_date' ? 'selected' : '' }}>Match Date</option>
                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name A-Z</option>
                </select>
                <div class="flex space-x-2">
                    <button class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium flex-1">Filter</button>
                    <a href="{{ route('demos.index') }}" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-4 py-2 rounded-lg text-sm">Reset</a>
                </div>
            </div>
        </form>

        {{-- Demo Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($demos as $demo)
                <a href="{{ route('demos.show', $demo) }}" class="block bg-gray-800 rounded-lg border border-gray-700 hover:border-amber-600 transition-colors overflow-hidden">
                    {{-- Header with game badge --}}
                    <div class="px-5 pt-5 pb-3">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-lg font-semibold text-white line-clamp-1 flex-1">{{ $demo->title }}</h3>
                            <span class="ml-2 bg-blue-600/20 text-blue-400 px-2 py-0.5 rounded text-xs font-medium shrink-0">{{ $demo->game }}</span>
                        </div>

                        {{-- Match info --}}
                        @if($demo->team_axis && $demo->team_allies)
                            <div class="text-amber-400 font-medium text-sm mb-2">
                                {{ $demo->team_axis }} <span class="text-gray-500">vs</span> {{ $demo->team_allies }}
                            </div>
                        @endif

                        <p class="text-gray-400 text-sm line-clamp-2 mb-3">{{ Str::limit(strip_tags($demo->description), 120) }}</p>

                        {{-- Tags row --}}
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            @if($demo->map_name)
                                <span class="bg-emerald-900/40 text-emerald-400 px-2 py-0.5 rounded text-xs">🗺️ {{ $demo->map_name }}</span>
                            @endif
                            @if($demo->mod_name)
                                <span class="bg-purple-900/40 text-purple-400 px-2 py-0.5 rounded text-xs">{{ $demo->mod_name }}</span>
                            @endif
                            @if($demo->gametype)
                                <span class="bg-gray-700 text-gray-300 px-2 py-0.5 rounded text-xs">{{ $demo->gametype }}</span>
                            @endif
                            @if($demo->match_format)
                                <span class="bg-gray-700 text-gray-300 px-2 py-0.5 rounded text-xs">{{ $demo->match_format }}</span>
                            @endif
                            @if($demo->demo_format)
                                <span class="bg-orange-900/40 text-orange-400 px-2 py-0.5 rounded text-xs">{{ $demo->demo_format }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-5 py-3 bg-gray-900/50 border-t border-gray-700 flex justify-between items-center text-xs text-gray-500">
                        <span>{{ $demo->file_size_formatted }}</span>
                        <span>↓ {{ number_format($demo->download_count) }}</span>
                        @if($demo->match_date)
                            <span>{{ $demo->match_date->format('d.m.Y') }}</span>
                        @else
                            <span>{{ $demo->created_at->format('d.m.Y') }}</span>
                        @endif
                        <span>by {{ $demo->user?->name ?? 'Unknown' }}</span>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center text-gray-400 py-12">
                    <div class="text-4xl mb-3">🎬</div>
                    <p>No demos found. Be the first to upload one!</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-8">{{ $demos->links() }}</div>
    </div>
</x-layouts.app>
