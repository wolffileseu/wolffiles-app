<x-layouts.app title="News">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
            <h1 class="text-3xl font-bold text-white">{{ __('messages.news') }}</h1>
            <form method="GET" action="{{ route('posts.index') }}" class="flex flex-wrap gap-2">
                <select name="type" onchange="this.form.submit()" class="bg-gray-800 border border-gray-700 text-gray-300 rounded px-3 py-1.5 text-sm">
                    <option value="">{{ __('messages.all_types') }}</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="clan" onchange="this.form.submit()" class="bg-gray-800 border border-gray-700 text-gray-300 rounded px-3 py-1.5 text-sm">
                    <option value="">{{ __('messages.all_clans') }}</option>
                    @foreach($clans as $clan)
                        <option value="{{ $clan->id }}" {{ request('clan') == $clan->id ? 'selected' : '' }}>[{{ $clan->tag }}] {{ $clan->name }}</option>
                    @endforeach
                </select>
                @if(request('type') || request('clan'))
                    <a href="{{ route('posts.index') }}" class="bg-gray-700 text-gray-300 hover:bg-gray-600 rounded px-3 py-1.5 text-sm">✕ Reset</a>
                @endif
            </form>
        </div>

        @if($pinned->isNotEmpty() && !request('type') && !request('clan'))
            @foreach($pinned as $post)
                <article class="bg-gray-800 rounded-lg border-2 border-amber-600/50 p-6 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="bg-amber-600/20 text-amber-400 px-2 py-0.5 rounded text-xs font-medium">📌 {{ __('messages.pinned') }}</span>
                        @include('frontend.posts._type_badge', ['post' => $post])
                        @if($post->clan)
                            <span class="bg-blue-900/30 text-blue-400 px-2 py-0.5 rounded text-xs">[{{ $post->clan->tag }}] {{ $post->clan->name }}</span>
                        @endif
                        <span class="text-gray-500 text-xs">{{ $post->published_at?->format('d.m.Y') }}</span>
                    </div>
                    @if($post->featured_image)
                        <img src="{{ Storage::disk('s3')->temporaryUrl($post->featured_image, now()->addHours(2)) }}" alt="{{ $post->title }}" class="w-full rounded-lg mb-4 max-h-64 object-cover">
                    @endif
                    <h2 class="text-2xl font-bold text-white mb-2">
                        <a href="{{ route('posts.show', $post) }}" class="hover:text-amber-400 transition-colors">{{ $post->title }}</a>
                    </h2>
                    <p class="text-gray-400">{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 300) }}</p>
                    <div class="mt-4 flex items-center space-x-4 text-sm text-gray-500">
                        <span>by {{ $post->user?->name }}</span>
                        <span>{{ $post->view_count }} views</span>
                        <a href="{{ route('posts.show', $post) }}" class="text-amber-400 hover:text-amber-300">{{ __('messages.read_more') }} →</a>
                    </div>
                </article>
            @endforeach
        @endif

        <div class="space-y-4">
            @forelse($posts as $post)
                <article class="bg-gray-800 rounded-lg border border-gray-700 p-5 hover:border-gray-600 transition-colors">
                    <div class="flex items-start gap-4">
                        @if($post->featured_image)
                            <img src="{{ Storage::disk('s3')->temporaryUrl($post->featured_image, now()->addHours(2)) }}" alt="" class="w-28 h-18 object-cover rounded flex-shrink-0">
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                @include('frontend.posts._type_badge', ['post' => $post])
                                @if($post->clan)
                                    <span class="bg-blue-900/30 text-blue-400 px-2 py-0.5 rounded text-xs">[{{ $post->clan->tag }}] {{ $post->clan->name }}</span>
                                @endif
                            </div>
                            <h2 class="text-lg font-semibold text-white mb-1">
                                <a href="{{ route('posts.show', $post) }}" class="hover:text-amber-400 transition-colors">{{ $post->title }}</a>
                            </h2>
                            @if($post->type === 'event' && $post->event_date)
                                <p class="text-green-400 text-xs mb-1">📅 {{ $post->event_date->format('d.m.Y H:i') }}{{ $post->event_location ? ' · ' . $post->event_location : '' }}</p>
                            @elseif($post->type === 'match' && $post->match_opponent)
                                <p class="text-yellow-400 text-xs mb-1">⚔️ vs {{ $post->match_opponent }}{{ $post->match_result ? ' · ' . $post->match_result : '' }}{{ $post->match_map ? ' · ' . $post->match_map : '' }}</p>
                            @endif
                            <p class="text-gray-400 text-sm mb-2 line-clamp-2">{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 200) }}</p>
                            <div class="flex items-center gap-4 text-xs text-gray-500">
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
