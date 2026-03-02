<x-layouts.app :title="__('messages.files')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-white">{{ __('messages.filebase') }}</h1>
            <div class="flex items-center space-x-4">
                {{-- #23 Sort Dropdown --}}
                @include('components.sort-dropdown', ['currentSort' => request('sort', 'newest')])

                @auth
                    <a href="{{ route('files.upload') }}" class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        {{ __('messages.upload_file') }}
                    </a>
                @endauth
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-4">
            <input type="hidden" name="sort" value="{{ request('sort', 'newest') }}">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search') }}..."
                       class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                <select name="category" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">{{ __('messages.all_categories') }}</option>
                    @foreach($categories as $cat)
                        <optgroup label="{{ $cat->name_translations[app()->getLocale()] ?? $cat->name }}">
                            @foreach($cat->children ?? collect() as $child)
                                <option value="{{ $child->slug }}" {{ request('category') == $child->slug ? 'selected' : '' }}>
                                    {{ $child->name_translations[app()->getLocale()] ?? $child->name }} ({{ $child->approved_files_count ?? 0 }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <select name="game" class="bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                    <option value="">{{ __('messages.all_games') }}</option>
                    @foreach($games as $game)
                        <option value="{{ $game }}" {{ request('game') == $game ? 'selected' : '' }}>{{ $game }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mt-4">
                <button class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm font-medium">{{ __('messages.filter') }}</button>
                <a href="{{ route('files.index') }}" class="text-gray-400 hover:text-gray-300 ml-4 text-sm">{{ __('messages.reset') }}</a>
            </div>
        </form>

        {{-- Tag Quick Filters --}}
        @php
            $popularTags = \App\Models\Tag::withCount(['files' => fn($q) => $q->where('status', 'approved')])
                ->having('files_count', '>', 0)
                ->orderByDesc('files_count')
                ->limit(20)
                ->get();
        @endphp
        @if($popularTags->isNotEmpty())
            <div class="flex flex-wrap gap-2 mb-6">
                <span class="text-gray-500 text-sm py-1">{{ __('messages.popular_tags') }}:</span>
                @foreach($popularTags as $pTag)
                    <a href="{{ route('files.index', array_merge(request()->only(['search', 'category', 'game', 'sort']), ['tag' => $pTag->slug])) }}"
                       class="px-3 py-1 rounded-full text-sm transition-all duration-150
                              {{ request('tag') == $pTag->slug
                                  ? 'bg-amber-600 text-white border border-amber-500'
                                  : 'bg-gray-800 text-gray-400 border border-gray-700 hover:border-amber-500 hover:text-amber-400' }}">
                        {{ $pTag->name }}
                        <span class="text-xs opacity-60">({{ $pTag->files_count }})</span>
                    </a>
                @endforeach
                @if(request('tag'))
                    <a href="{{ route('files.index', request()->except('tag')) }}"
                       class="px-3 py-1 rounded-full text-sm bg-red-900/30 text-red-400 border border-red-800 hover:bg-red-900/50 transition-colors">
                        ✕ {{ __('messages.reset') }}
                    </a>
                @endif
            </div>
        @endif

        {{-- Active tag info --}}
        @if(request('tag'))
            @php $activeTag = \App\Models\Tag::where('slug', request('tag'))->first(); @endphp
            @if($activeTag)
                <div class="bg-amber-600/10 border border-amber-600/30 rounded-lg px-4 py-3 mb-6 flex items-center justify-between">
                    <span class="text-amber-400 text-sm">
                        Filtered by tag: <strong>{{ $activeTag->name }}</strong>
                        — {{ $files->total() }} {{ __('messages.results_found') }}
                    </span>
                    <a href="{{ route('files.index', request()->except('tag')) }}" class="text-amber-400 hover:text-white text-sm">✕ Remove filter</a>
                </div>
            @endif
        @endif

        {{-- Results --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($files as $file)
                @include('components.file-card', ['file' => $file])
            @empty
                <div class="col-span-full text-center text-gray-400 py-12">
                    <p class="text-xl mb-2">{{ __('messages.no_files_found') }}</p>
                    <p>{{ __('messages.try_different_search') }} <a href="{{ route('files.index') }}" class="text-amber-400">{{ __('messages.browse_all') }}</a></p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-8">{{ $files->links() }}</div>
    </div>
</x-layouts.app>
