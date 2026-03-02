<x-layouts.app title="Home">
    {{-- Hero Section --}}
    <div class="bg-gradient-to-b from-gray-800 to-gray-900 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold text-amber-400 mb-4">Wolffiles.eu</h1>
            <p class="text-xl text-gray-300 mb-8">{{ __('messages.hero_subtitle') }}</p>

            {{-- Stats --}}
            <div class="flex justify-center space-x-8 mb-8">
                <div class="text-center">
                    <div class="text-3xl font-bold text-white">{{ number_format($stats['total_files']) }}</div>
                    <div class="text-gray-400 text-sm">{{ __('messages.files') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white">{{ number_format($stats['total_downloads']) }}</div>
                    <div class="text-gray-400 text-sm">{{ __('messages.downloads') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white">{{ number_format($stats['total_maps']) }}</div>
                    <div class="text-gray-400 text-sm">Maps</div>
                </div>
            </div>

            {{-- Search --}}
            <form action="{{ route('files.index') }}" method="GET" class="max-w-xl mx-auto">
                <div class="flex">
                    <input type="text" name="search" placeholder="{{ __('messages.search_placeholder') }}"
                           class="flex-1 bg-gray-700 border-gray-600 text-white rounded-l-lg px-6 py-3 focus:ring-amber-500 focus:border-amber-500">
                    <button class="bg-amber-600 hover:bg-amber-700 text-white px-8 py-3 rounded-r-lg font-medium transition-colors">
                        {{ __('messages.search') }}
                    </button>
                </div>
            </form>

            {{-- Donate Button --}}
            <div class="mt-6">
                <a href="{{ route('donate') }}"
                   class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-medium transition"
                   style="background: linear-gradient(to right, #f59e0b, #ea580c); color: white;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    {{ __('messages.donate') }} — {{ __('messages.donate_subtitle') }}
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- #14 Map of the Week / Spotlight --}}
        @include('components.spotlight')

        {{-- #31 Trending --}}
        @include('components.trending')

        {{-- Featured Files --}}
        @if($featuredFiles->isNotEmpty())
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-amber-400 mb-6">⭐ {{ __('messages.featured_files') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($featuredFiles as $file)
                    @include('components.file-card', ['file' => $file])
                @endforeach
            </div>
        </section>
        @endif

        {{-- Latest Files --}}
        <section class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-white">{{ __('messages.latest_files') }}</h2>
                <a href="{{ route('files.index') }}" class="text-amber-400 hover:text-amber-300 text-sm">{{ __('messages.view_all') }} →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($latestFiles as $file)
                    @include('components.file-card', ['file' => $file])
                @endforeach
            </div>
        </section>

        {{-- #15 Hall of Fame --}}
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">🏆 {{ __('messages.hall_of_fame') }}</h2>
            @include('components.hall-of-fame')
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Sidebar Left: Categories + Poll + Recently Viewed --}}
            <section class="lg:col-span-1 space-y-6">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-6">{{ __('messages.categories') }}</h2>
                    <div class="bg-gray-800 rounded-lg border border-gray-700 divide-y divide-gray-700">
                        @foreach($categories as $category)
                            <a href="{{ route('categories.show', $category) }}"
                               class="flex justify-between items-center px-4 py-3 hover:bg-gray-700/50 transition-colors">
                                <span class="text-gray-300">{{ $category->name_translations[app()->getLocale()] ?? $category->name }}</span>
                                <span class="bg-gray-700 text-gray-400 text-xs px-2 py-1 rounded-full">{{ $category->approved_files_count ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- #34 Poll --}}
                @include('components.poll')

                {{-- #24 Recently Viewed --}}
                @include('components.recently-viewed')
            </section>

            {{-- Latest News --}}
            <section class="lg:col-span-2">
                <h2 class="text-2xl font-bold text-white mb-6">{{ __('messages.news') }}</h2>
                <div class="space-y-4">
                    @foreach($latestPosts as $post)
                        @php
                            $cleanContent = $post->content ?? '';
                            $cleanContent = strip_tags($cleanContent);
                            $cleanContent = preg_replace('/[#*_~`>]+/', '', $cleanContent);
                            $cleanContent = html_entity_decode($cleanContent, ENT_QUOTES, 'UTF-8');
                            $cleanContent = preg_replace('/\s+/', ' ', trim($cleanContent));
                            $cleanContent = Str::limit($cleanContent, 200);

                            // Build proper image URL from S3
                            $imageUrl = null;
                            if ($post->featured_image) {
                                if (Str::startsWith($post->featured_image, ['http://', 'https://'])) {
                                    $imageUrl = $post->featured_image;
                                } else {
                                    $imageUrl = Storage::disk('s3')->url($post->featured_image);
                                }
                            }
                        @endphp
                        <article class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                            <div class="flex items-start space-x-4">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $post->title }}"
                                         class="w-24 h-24 object-cover rounded-lg flex-shrink-0" loading="lazy" width="96" height="96"
                                         onerror="this.style.display='none'">
                                @endif
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-semibold text-amber-400 mb-2">
                                        <a href="{{ route('posts.show', $post) }}" class="hover:underline">{{ $post->title }}</a>
                                    </h3>
                                    <p class="text-gray-400 text-sm mb-3">{{ $cleanContent }}</p>
                                    <div class="flex items-center space-x-3 text-xs text-gray-500">
                                        <span>{{ $post->published_at?->format('d.m.Y') }}</span>
                                        <span>von {{ $post->user?->name }}</span>
                                        <a href="{{ route('posts.show', $post) }}" class="text-amber-400 hover:underline ml-auto">Weiterlesen →</a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach

                    @if($latestPosts->isEmpty())
                        <p class="text-gray-500 text-center py-8">Keine Neuigkeiten.</p>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
