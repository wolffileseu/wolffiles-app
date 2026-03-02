<x-layouts.app title="News">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-white mb-8">{{ __('messages.news') }}</h1>

        {{-- Pinned Posts --}}
        @if($pinned->isNotEmpty())
            @foreach($pinned as $post)
                <article class="bg-gray-800 rounded-lg border-2 border-amber-600/50 p-6 mb-6">
                    <div class="flex items-center space-x-2 mb-3">
                        <span class="bg-amber-600/20 text-amber-400 px-2 py-0.5 rounded text-xs font-medium">📌 {{ __('messages.pinned') }}</span>
                        <span class="text-gray-500 text-xs">{{ $post->published_at?->format('d.m.Y') }}</span>
                    </div>
                    @if($post->featured_image)
                        <img src="{{ Storage::disk('s3')->temporaryUrl($post->featured_image, now()->addHours(2)) }}" alt="{{ $post->title }}" class="w-full rounded-lg mb-4 max-h-64 object-cover">
                    @endif
                    <h2 class="text-2xl font-bold text-white mb-2">
                        <a href="{{ route('posts.show', $post) }}" class="hover:text-amber-400 transition-colors">{{ $post->title }}</a>
                    </h2>
                    <p class="text-gray-400">{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 300) }}</p>
                    @if($post->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach($post->tags as $tag)
                                <span class="bg-gray-700 text-gray-300 px-2 py-0.5 rounded-full text-xs">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="mt-4 flex items-center space-x-4 text-sm text-gray-500">
                        <span>by {{ $post->user?->name }}</span>
                        <span>{{ $post->view_count }} views</span>
                        <a href="{{ route('posts.show', $post) }}" class="text-amber-400 hover:text-amber-300">{{ __('messages.read_more') }} →</a>
                    </div>
                </article>
            @endforeach
        @endif

        {{-- All Posts --}}
        <div class="space-y-6">
            @forelse($posts as $post)
                <article class="bg-gray-800 rounded-lg border border-gray-700 p-6 hover:border-gray-600 transition-colors">
                    <div class="flex items-start space-x-4">
                        @if($post->featured_image)
                            <img src="{{ Storage::disk('s3')->temporaryUrl($post->featured_image, now()->addHours(2)) }}" alt="" class="w-32 h-20 object-cover rounded flex-shrink-0">
                        @endif
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-semibold text-white mb-1">
                                <a href="{{ route('posts.show', $post) }}" class="hover:text-amber-400 transition-colors">{{ $post->title }}</a>
                            </h2>
                            <p class="text-gray-400 text-sm mb-2 line-clamp-2">{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 200) }}</p>
                            @if($post->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-1 mb-2">
                                    @foreach($post->tags as $tag)
                                        <span class="bg-gray-700 text-gray-300 px-2 py-0.5 rounded-full text-xs">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span>{{ $post->published_at?->format('d.m.Y') }}</span>
                                <span>by {{ $post->user?->name }}</span>
                                <span>{{ $post->view_count }} views</span>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="text-center text-gray-400 py-12">{{ __('messages.no_files') }}</div>
            @endforelse
        </div>

        <div class="mt-8">{{ $posts->links() }}</div>
    </div>
</x-layouts.app>