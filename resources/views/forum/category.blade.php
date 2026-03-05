<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-8">

        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('forum.index') }}" class="hover:text-white transition">{{ __('messages.forum_title') }}</a>
            <span class="mx-2">›</span>
            @if($category->parent)
                <a href="{{ route('forum.category', $category->parent) }}" class="hover:text-white transition">
                    {{ $category->parent->name }}
                </a>
                <span class="mx-2">›</span>
            @endif
            <span class="text-white">{{ $category->translated_name }}</span>
        </nav>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $category->translated_name }}</h1>
                @if($category->translated_description)
                    <p class="text-gray-400 mt-1">{{ $category->translated_description }}</p>
                @endif
            </div>
            @auth
                @unless($category->is_locked)
                <a href="{{ route('forum.create-thread', $category) }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline">{{ __('messages.forum_new_thread') }}</span>
                </a>
                @endunless
            @endauth
        </div>

        @if($subcategories->isNotEmpty())
        <div class="mb-6 border border-gray-700 rounded-lg divide-y divide-gray-700">
            @foreach($subcategories as $sub)
            <a href="{{ route('forum.category', $sub) }}"
               class="flex items-center justify-between px-5 py-3 hover:bg-gray-800/50 transition block">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full" style="background-color: {{ $sub->color }}"></div>
                    <span class="text-white font-medium">{{ $sub->translated_name }}</span>
                    <span class="text-gray-500 text-sm">{{ $sub->threads_count }} {{ __('messages.forum_threads') }}</span>
                </div>
                <i class="fas fa-chevron-right text-gray-600"></i>
            </a>
            @endforeach
        </div>
        @endif

        <div class="border border-gray-700 rounded-lg overflow-hidden">
            <div class="hidden sm:grid grid-cols-12 gap-4 px-5 py-3 bg-gray-800 text-xs uppercase text-gray-400 font-semibold">
                <div class="col-span-6">Thread</div>
                <div class="col-span-1 text-center">{{ __('messages.forum_replies') }}</div>
                <div class="col-span-1 text-center">{{ __('messages.forum_views') }}</div>
                <div class="col-span-4 text-right">{{ __('messages.forum_last_reply') }}</div>
            </div>

            <div class="divide-y divide-gray-700">
                @forelse($threads as $thread)
                <div class="sm:grid sm:grid-cols-12 gap-4 px-5 py-4 hover:bg-gray-800/30 transition items-center">
                    <div class="sm:col-span-6">
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($thread->is_pinned)
                                <span class="text-yellow-500 text-xs"><i class="fas fa-thumbtack"></i></span>
                            @endif
                            @if($thread->is_locked)
                                <span class="text-red-500 text-xs"><i class="fas fa-lock"></i></span>
                            @endif
                            <a href="{{ route('forum.thread', [$category, $thread]) }}"
                               class="text-white hover:text-blue-400 transition font-medium">
                                {{ $thread->title }}
                            </a>
                        </div>
                        <div class="text-sm text-gray-500 mt-1">
                            {{ __('messages.forum_by') }} <span class="text-gray-300">{{ $thread->user->name }}</span>
                            · {{ $thread->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <div class="hidden sm:block sm:col-span-1 text-center text-gray-400">
                        {{ $thread->posts_count - 1 }}
                    </div>
                    <div class="hidden sm:block sm:col-span-1 text-center text-gray-400">
                        {{ $thread->views_count }}
                    </div>
                    <div class="hidden sm:block sm:col-span-4 text-right text-sm text-gray-400">
                        @if($thread->lastPostUser)
                            <span class="text-gray-300">{{ $thread->lastPostUser->name }}</span><br>
                            {{ $thread->last_post_at?->diffForHumans() }}
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-12 text-center text-gray-500">
                    <i class="fas fa-comments text-3xl mb-3 opacity-50"></i>
                    <p>{{ __('messages.forum_no_threads') }}</p>
                    @auth
                    <a href="{{ route('forum.create-thread', $category) }}"
                       class="text-blue-400 hover:text-blue-300 mt-2 inline-block">
                        {{ __('messages.forum_first_thread') }}
                    </a>
                    @endauth
                </div>
                @endforelse
            </div>
        </div>

        <div class="mt-6">
            {{ $threads->links() }}
        </div>
    </div>
</x-app-layout>
