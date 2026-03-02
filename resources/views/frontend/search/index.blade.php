<x-layouts.app :title="__('messages.advanced_search')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-6">{{ __('messages.advanced_search') }}</h1>

        <form method="GET" action="{{ route('search') }}" class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Search Text --}}
                <div class="lg:col-span-3">
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.search') }}</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('messages.search_placeholder') }}"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2">
                </div>

                {{-- Category --}}
                <div>
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.category') }}</label>
                    <select name="category_id" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                        <option value="">{{ __('messages.all_categories') }}</option>
                        @foreach($categories as $cat)
                            <optgroup label="{{ $cat->name }}">
                                @foreach($cat->children ?? collect() as $child)
                                    <option value="{{ $child->id }}" {{ request('category_id') == $child->id ? 'selected' : '' }}>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- Game --}}
                <div>
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.game') }}</label>
                    <select name="game" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                        <option value="">{{ __('messages.all_games') }}</option>
                        @foreach($games as $game)
                            <option value="{{ $game }}" {{ request('game') == $game ? 'selected' : '' }}>{{ $game }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Author --}}
                <div>
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.author') }}</label>
                    <input type="text" name="author" value="{{ request('author') }}" placeholder="Map author..."
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                </div>

                {{-- File Size --}}
                <div>
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.min_size') }} (MB)</label>
                    <input type="number" name="min_size" value="{{ request('min_size') }}" step="0.1" min="0"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.max_size') }} (MB)</label>
                    <input type="number" name="max_size" value="{{ request('max_size') }}" step="0.1" min="0"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                </div>

                {{-- Min Rating --}}
                <div>
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.min_rating') }}</label>
                    <select name="min_rating" class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                        <option value="">-</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ request('min_rating') == $i ? 'selected' : '' }}>{{ $i }}+ ★</option>
                        @endfor
                    </select>
                </div>

                {{-- Date Range --}}
                <div>
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.date_from') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">{{ __('messages.date_to') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm">
                </div>

                {{-- Has Screenshots --}}
                <div class="flex items-end">
                    <label class="flex items-center space-x-2 cursor-pointer py-2">
                        <input type="checkbox" name="has_screenshots" value="1" {{ request('has_screenshots') ? 'checked' : '' }}
                               class="bg-gray-700 border-gray-600 text-amber-500 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-300">{{ __('messages.with_screenshots') }}</span>
                    </label>
                </div>
            </div>

            {{-- Popular Tags --}}
            @if($popularTags->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-gray-700">
                    <span class="text-xs text-gray-500 mr-2">{{ __('messages.popular_tags') }}:</span>
                    @foreach($popularTags as $tag)
                        <a href="{{ route('search', ['tag' => $tag->slug]) }}"
                           class="inline-block text-xs px-2 py-1 rounded {{ request('tag') == $tag->slug ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-400 hover:text-white' }} transition-colors mr-1 mb-1">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 flex items-center space-x-4">
                <button class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-2 rounded-lg font-medium transition-colors">
                    {{ __('messages.search') }}
                </button>
                <a href="{{ route('search') }}" class="text-gray-400 hover:text-gray-300 text-sm">{{ __('messages.reset') }}</a>
            </div>
        </form>

        {{-- Results --}}
        @if(request()->hasAny(['q', 'category_id', 'game', 'author', 'tag']))
            <div class="flex items-center justify-between mb-4">
                <p class="text-gray-400 text-sm">{{ $files->total() }} {{ __('messages.results_found') }}</p>
                @include('components.sort-dropdown', ['currentSort' => request('sort', 'relevance')])
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @forelse($files as $file)
                    @include('components.file-card', ['file' => $file])
                @empty
                    <div class="col-span-full text-center text-gray-400 py-12">
                        <p class="text-xl mb-2">{{ __('messages.no_files_found') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">{{ $files->links() }}</div>
        @endif
    </div>
</x-layouts.app>
