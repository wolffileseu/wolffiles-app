<x-layouts.app :title="__('messages.categories')">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">{{ __('messages.home') }}</a> /
            <span class="text-gray-300">{{ __('messages.categories') }}</span>
        </nav>

        <h1 class="text-3xl font-bold text-white mb-8">{{ __('messages.categories') }}</h1>

        @if($categories->isEmpty())
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-12 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <p class="text-gray-400 text-lg">{{ __('messages.no_categories') }}</p>
            </div>
        @else
            <div class="space-y-8">
                @foreach($categories as $category)
                    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                        {{-- Parent Category Header --}}
                        <a href="{{ route('categories.show', $category) }}"
                           class="flex items-center justify-between p-5 hover:bg-gray-750 transition-colors group">
                            <div class="flex items-center space-x-4">
                                @if($category->icon)
                                    <span class="text-3xl">{{ $category->icon }}</span>
                                @else
                                    <div class="w-10 h-10 bg-amber-600/20 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <h2 class="text-xl font-semibold text-white group-hover:text-amber-400 transition-colors">
                                        {{ $category->name_translations[app()->getLocale()] ?? $category->name }}
                                    </h2>
                                    @if($category->description)
                                        <p class="text-sm text-gray-400 mt-1">{{ Str::limit($category->description, 100) }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="bg-gray-700 text-gray-300 px-3 py-1 rounded-full text-sm">
                                    {{ $category->approved_files_count ?? 0 }} {{ __('messages.files') }}
                                </span>
                                <svg class="w-5 h-5 text-gray-500 group-hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </a>

                        {{-- Subcategories --}}
                        @if($category->children->isNotEmpty())
                            <div class="border-t border-gray-700 px-5 py-4 bg-gray-800/50">
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                    @foreach($category->children as $child)
                                        <a href="{{ route('categories.show', $child) }}"
                                           class="flex items-center justify-between bg-gray-700/50 hover:bg-gray-700 rounded-lg px-4 py-3 transition-colors group">
                                            <span class="text-sm text-gray-300 group-hover:text-amber-400 truncate">
                                                {{ $child->name_translations[app()->getLocale()] ?? $child->name }}
                                            </span>
                                            <span class="text-xs text-gray-500 ml-2 flex-shrink-0">{{ $child->approved_files_count ?? 0 }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
