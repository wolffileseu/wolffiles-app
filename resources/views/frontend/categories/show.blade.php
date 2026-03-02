<x-layouts.app :title="$category->name">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">{{ __('messages.home') }}</a> /
            <a href="{{ route('categories.index') }}" class="hover:text-amber-400">{{ __('messages.categories') }}</a> /
            @if($category->parent)
                <a href="{{ route('categories.show', $category->parent) }}" class="hover:text-amber-400">{{ $category->parent->name }}</a> /
            @endif
            <span class="text-gray-300">{{ $category->name_translations[app()->getLocale()] ?? $category->name }}</span>
        </nav>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-white">{{ $category->name_translations[app()->getLocale()] ?? $category->name }}</h1>
                @if($category->description)
                    <p class="text-gray-400 mt-2">{{ $category->description }}</p>
                @endif
            </div>
            <span class="bg-gray-700 text-gray-300 px-4 py-2 rounded-full text-sm">
                {{ $files->total() }} {{ __('messages.files') }}
            </span>
        </div>

        {{-- Subcategories --}}
        @if($category->children->isNotEmpty())
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach($category->children as $child)
                    <a href="{{ route('categories.show', $child) }}"
                       class="bg-gray-700/50 hover:bg-gray-700 text-gray-300 hover:text-amber-400 px-4 py-2 rounded-lg text-sm transition-colors">
                        {{ $child->name_translations[app()->getLocale()] ?? $child->name }}
                        <span class="text-gray-500 ml-1">({{ $child->approved_files_count ?? 0 }})</span>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Files Grid --}}
        @if($files->isEmpty())
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-12 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <p class="text-gray-400 text-lg">{{ __('messages.no_files_in_category') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($files as $file)
                    <a href="{{ route('files.show', $file) }}"
                       class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden hover:border-amber-500/50 transition-colors group">
                        {{-- Thumbnail --}}
                        <div class="aspect-video bg-gray-700 overflow-hidden">
                            @if($file->screenshots->isNotEmpty())
                                <img src="{{ $file->screenshots->first()->thumbnail_url ?? $file->screenshots->first()->url }}"
                                     alt="{{ $file->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="p-4">
                            <h3 class="text-sm font-medium text-gray-200 group-hover:text-amber-400 truncate transition-colors">
                                {{ $file->title }}
                            </h3>
                            <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                                <span>{{ $file->file_size_formatted ?? '-' }}</span>
                                <span>↓ {{ number_format($file->download_count) }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $files->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
