<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 py-8">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">{{ __('messages.forum_title') }}</h1>
                <p class="text-gray-400 mt-1">{{ __('messages.forum_subtitle') }}</p>
            </div>
        </div>

        @foreach($categories as $category)
        <div class="mb-8">
            <div class="bg-gray-800 border border-gray-700 rounded-t-lg px-6 py-4">
                <div class="flex items-center gap-3">
                    @if($category->icon)
                        <i class="{{ $category->icon }} text-xl" style="color: {{ $category->color }}"></i>
                    @else
                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $category->color }}"></div>
                    @endif
                    <h2 class="text-lg font-semibold text-white">{{ $category->name }}</h2>
                </div>
                @if($category->description)
                    <p class="text-gray-400 text-sm mt-1 ml-8">{{ $category->description }}</p>
                @endif
            </div>

            <div class="border border-t-0 border-gray-700 rounded-b-lg divide-y divide-gray-700">
                @forelse($category->children as $sub)
                <a href="{{ route('forum.category', $sub) }}"
                   class="flex items-center justify-between px-6 py-4 hover:bg-gray-800/50 transition group block">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                             style="background-color: {{ $sub->color }}20">
                            @if($sub->icon)
                                <i class="{{ $sub->icon }}" style="color: {{ $sub->color }}"></i>
                            @else
                                <i class="fas fa-comments" style="color: {{ $sub->color }}"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-white font-medium group-hover:text-blue-400 transition">
                                {{ $sub->name }}
                            </h3>
                            @if($sub->description)
                                <p class="text-gray-500 text-sm">{{ $sub->description }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-8 text-sm text-gray-400">
                        <div class="text-center hidden sm:block">
                            <div class="font-semibold text-white">{{ $sub->threads_count }}</div>
                            <div>{{ __('messages.forum_threads') }}</div>
                        </div>
                        <div class="text-center hidden sm:block">
                            <div class="font-semibold text-white">{{ $sub->posts_count }}</div>
                            <div>{{ __('messages.forum_posts') }}</div>
                        </div>
                        @if($latest = $sub->latest_post)
                        <div class="w-48 text-right hidden lg:block">
                            <div class="text-white truncate">{{ $latest->thread->title }}</div>
                            <div>{{ __('messages.forum_by') }} {{ $latest->user->name }} · {{ $latest->created_at->diffForHumans() }}</div>
                        </div>
                        @endif
                    </div>
                </a>
                @empty
                <a href="{{ route('forum.category', $category) }}"
                   class="flex items-center justify-between px-6 py-4 hover:bg-gray-800/50 transition block">
                    <span class="text-gray-400">{{ __('messages.forum_go_to_threads') }}</span>
                </a>
                @endforelse
            </div>
        </div>
        @endforeach

        <div class="mt-8 bg-gray-800/50 border border-gray-700 rounded-lg px-6 py-4">
            <div class="flex flex-wrap items-center gap-6 text-sm text-gray-400">
                <span><i class="fas fa-layer-group mr-1"></i> {{ \App\Models\ForumCategory::count() }} {{ __('messages.forum_categories') }}</span>
                <span><i class="fas fa-comments mr-1"></i> {{ \App\Models\ForumThread::count() }} {{ __('messages.forum_threads') }}</span>
                <span><i class="fas fa-comment mr-1"></i> {{ \App\Models\ForumPost::count() }} {{ __('messages.forum_posts') }}</span>
                <span><i class="fas fa-users mr-1"></i> {{ \App\Models\User::count() }} {{ __('messages.forum_members') }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
