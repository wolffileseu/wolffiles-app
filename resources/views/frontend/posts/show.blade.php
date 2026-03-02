<x-layouts.app :title="$post->title" :seo="$seo ?? []" :jsonLd="$jsonLd ?? []">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">Home</a> /
            <a href="{{ route('posts.index') }}" class="hover:text-amber-400">News</a> /
            <span class="text-gray-300">{{ Str::limit($post->title, 50) }}</span>
        </nav>

        <article>
            @if($post->featured_image)
                <img src="{{ Storage::disk('s3')->url($post->featured_image) }}" alt="{{ $post->title }}" class="w-full rounded-lg mb-6 max-h-96 object-cover">
            @endif

            <h1 class="text-3xl font-bold text-white mb-4">{{ $post->title }}</h1>

            <div class="flex items-center space-x-4 text-sm text-gray-500 mb-8">
                <div class="flex items-center space-x-2">
                    <img src="{{ $post->user?->avatar_url }}" class="w-6 h-6 rounded-full">
                    <a href="{{ route('profile.show', $post->user) }}" class="text-amber-400 hover:underline">{{ $post->user?->name }}</a>
                </div>
                <span>{{ $post->published_at?->format('d. F Y') }}</span>
                <span>{{ $post->view_count }} views</span>
            </div>

            @if($post->tags->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-6">
                    @foreach($post->tags as $tag)
                        <span class="bg-gray-700 text-gray-300 px-3 py-1 rounded-full text-xs">{{ $tag->name }}</span>
                    @endforeach
                </div>
            @endif

            <div class="bg-gray-800 rounded-lg border border-gray-700 p-8 prose prose-invert max-w-none">
                {!! $post->content !!}
            </div>
        </article>

        {{-- Comments --}}
        <div class="mt-8">
            @include('components.comments', ['commentable' => $post, 'type' => 'post'])
        </div>
    </div>
</x-layouts.app>
