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

            <div class="flex flex-wrap items-center gap-2 mb-3">
                @include('frontend.posts._type_badge', ['post' => $post])
                @if($post->clan)
                    <span class="bg-blue-900/30 text-blue-400 px-2 py-0.5 rounded text-xs">[{{ $post->clan->tag }}] {{ $post->clan->name }}</span>
                @endif
            </div>

            <h1 class="text-3xl font-bold text-white mb-4">{{ $post->title }}</h1>

            <div class="flex items-center space-x-4 text-sm text-gray-500 mb-6">
                <div class="flex items-center space-x-2">
                    <img src="{{ $post->user?->avatar_url }}" class="w-6 h-6 rounded-full">
                    <a href="{{ route('profile.show', $post->user) }}" class="text-amber-400 hover:underline">{{ $post->user?->name }}</a>
                </div>
                <span>{{ $post->published_at?->format('d. F Y') }}</span>
                <span>{{ $post->view_count }} views</span>
            </div>

            @if($post->type === 'event' && $post->event_date)
                <div class="bg-green-900/20 border border-green-700/40 rounded-lg p-4 mb-6">
                    <h3 class="text-green-400 font-semibold mb-2">📅 {{ __('messages.event_details') }}</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-400">Datum & Uhrzeit:</span>
                            <span class="text-white ml-2">{{ $post->event_date->format('d.m.Y H:i') }}</span>
                        </div>
                        @if($post->event_location)
                        <div>
                            <span class="text-gray-400">Ort / Server:</span>
                            <span class="text-white ml-2">{{ $post->event_location }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($post->type === 'match' && $post->match_opponent)
                <div class="bg-yellow-900/20 border border-yellow-700/40 rounded-lg p-4 mb-6">
                    <h3 class="text-yellow-400 font-semibold mb-2">⚔️ {{ __('messages.match_details') }}</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-400">Gegner:</span>
                            <span class="text-white ml-2">{{ $post->match_opponent }}</span>
                        </div>
                        @if($post->match_result)
                        <div>
                            <span class="text-gray-400">Ergebnis:</span>
                            <span class="text-white font-bold ml-2">{{ $post->match_result }}</span>
                        </div>
                        @endif
                        @if($post->match_map)
                        <div>
                            <span class="text-gray-400">Map:</span>
                            <span class="text-white ml-2">{{ $post->match_map }}</span>
                        </div>
                        @endif
                        @if($post->event_date)
                        <div>
                            <span class="text-gray-400">Datum:</span>
                            <span class="text-white ml-2">{{ $post->event_date->format('d.m.Y') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($post->type === 'recruitment' && $post->recruitment_requirements)
                <div class="bg-red-900/20 border border-red-700/40 rounded-lg p-4 mb-6">
                    <h3 class="text-red-400 font-semibold mb-2">🎮 {{ __('messages.requirements') }}</h3>
                    <ul class="space-y-1">
                        @foreach($post->recruitment_requirements as $req)
                            <li class="text-gray-300 text-sm flex items-center gap-2">
                                <span class="text-red-400">✓</span>
                                {{ is_array($req) ? ($req['requirement'] ?? $req) : $req }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

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

        <div class="mt-8">
            @include('components.comments', ['commentable' => $post, 'type' => 'post'])
        </div>
    </div>
</x-layouts.app>
