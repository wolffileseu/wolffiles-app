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
            <a href="{{ route('forum.category', $category) }}" class="hover:text-white transition">
                {{ $category->name }}
            </a>
            <span class="mx-2">›</span>
            <span class="text-white">{{ Str::limit($thread->title, 40) }}</span>
        </nav>

        <div class="flex items-start justify-between mb-6 gap-4">
            <h1 class="text-2xl font-bold text-white flex items-center gap-3 flex-wrap">
                @if($thread->is_pinned)
                    <span class="text-yellow-500 text-lg"><i class="fas fa-thumbtack"></i></span>
                @endif
                @if($thread->is_locked)
                    <span class="text-red-500 text-lg"><i class="fas fa-lock"></i></span>
                @endif
                {{ $thread->title }}
            </h1>

            @auth
                @if(auth()->user()->hasRole(['admin', 'moderator']))
                <div class="flex items-center gap-2 flex-shrink-0" x-data="{ modOpen: false }">
                    <button @click="modOpen = !modOpen"
                            class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-3 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-shield-halved mr-1"></i> {{ __('messages.forum_mod') }}
                    </button>

                    <div x-show="modOpen" @click.away="modOpen = false" x-cloak
                         class="absolute right-4 mt-48 bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-50 py-2 w-56">

                        <form method="POST" action="{{ route('forum.toggle-pin', $thread) }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition">
                                <i class="fas fa-thumbtack mr-2 w-4"></i>
                                {{ $thread->is_pinned ? __('messages.forum_unpin') : __('messages.forum_pin') }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('forum.toggle-lock', $thread) }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition">
                                <i class="fas fa-{{ $thread->is_locked ? 'lock-open' : 'lock' }} mr-2 w-4"></i>
                                {{ $thread->is_locked ? __('messages.forum_unlock') : __('messages.forum_lock') }}
                            </button>
                        </form>

                        <div x-data="{ moveOpen: false }" class="border-t border-gray-700">
                            <button @click="moveOpen = !moveOpen"
                                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition">
                                <i class="fas fa-arrows-alt mr-2 w-4"></i> {{ __('messages.forum_move') }}
                            </button>
                            <div x-show="moveOpen" class="px-4 pb-2">
                                <form method="POST" action="{{ route('forum.move-thread', $thread) }}">
                                    @csrf
                                    <select name="forum_category_id"
                                            class="w-full bg-gray-900 border border-gray-600 rounded text-sm text-white px-2 py-1 mb-2">
                                        @foreach(\App\Models\ForumCategory::whereNotNull('parent_id')->orderBy('name')->get() as $cat)
                                            <option value="{{ $cat->id }}" {{ $cat->id === $thread->forum_category_id ? 'selected' : '' }}>
                                                {{ $cat->parent?->name }} → {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded w-full">
                                        {{ __('messages.forum_move_submit') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if(auth()->user()->hasRole('admin'))
                        <div class="border-t border-gray-700">
                            <form method="POST" action="{{ route('forum.delete-thread', $thread) }}"
                                  onsubmit="return confirm('{{ __('messages.forum_confirm_delete_thread') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-gray-700 transition">
                                    <i class="fas fa-trash mr-2 w-4"></i> {{ __('messages.forum_delete_thread') }}
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            @endauth
        </div>

        <div class="flex items-center gap-4 text-sm text-gray-500 mb-6">
            <span><i class="fas fa-eye mr-1"></i> {{ $thread->views_count }} {{ __('messages.forum_views') }}</span>
            <span><i class="fas fa-comment mr-1"></i> {{ $thread->posts_count }} {{ __('messages.forum_posts') }}</span>
        </div>

        <div class="space-y-4">
            @foreach($posts as $post)
            <div class="bg-gray-800/50 border border-gray-700 rounded-lg overflow-hidden" id="post-{{ $post->id }}">
                <div class="flex flex-col md:flex-row">
                    <div class="md:w-44 bg-gray-800/80 p-4 flex md:flex-col items-center gap-3 border-b md:border-b-0 md:border-r border-gray-700">
                        <div class="w-12 h-12 rounded-full bg-gray-600 flex items-center justify-center text-white font-bold text-lg">
                            {{ strtoupper(substr($post->user->name, 0, 1)) }}
                        </div>
                        <div class="text-center">
                            <div class="text-white font-medium text-sm">{{ $post->user->name }}</div>
                            @if($post->user->hasRole('admin'))
                                <span class="text-xs text-red-400">{{ __('messages.forum_admin') }}</span>
                            @elseif($post->user->hasRole('moderator'))
                                <span class="text-xs text-yellow-400">{{ __('messages.forum_moderator') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <a href="#post-{{ $post->id }}" class="text-gray-500 text-sm hover:text-gray-300 transition">
                                {{ $post->created_at->format('d.m.Y, H:i') }}
                                ({{ $post->created_at->diffForHumans() }})
                            </a>
                            <div class="flex items-center gap-2">
                                @if($post->is_solution)
                                    <span class="bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded">
                                        <i class="fas fa-check mr-1"></i> {{ __('messages.forum_solution') }}
                                    </span>
                                @endif
                                @auth
                                    @if($post->canEdit(auth()->user()))
                                    <a href="{{ route('forum.edit-post', $post) }}"
                                       class="text-gray-500 hover:text-white text-sm transition">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    @if($post->canDelete(auth()->user()))
                                    <form method="POST" action="{{ route('forum.delete-post', $post) }}"
                                          class="inline"
                                          onsubmit="return confirm('{{ __('messages.forum_confirm_delete_post') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-500 hover:text-red-400 text-sm transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                @endauth
                            </div>
                        </div>

                        <div class="prose prose-invert max-w-none text-gray-300 break-words">
                            {!! \App\Helpers\BBCode::parse($post->body) !!}
                        </div>

                        @if($post->edited_at)
                        <div class="mt-4 text-xs text-gray-600 italic">
                            <i class="fas fa-pencil-alt mr-1"></i>
                            {{ __('messages.forum_edited_by', ['name' => $post->editor?->name ?? 'Unknown', 'date' => $post->edited_at->format('d.m.Y, H:i')]) }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $posts->links() }}
        </div>

        @auth
            @unless($thread->is_locked)
            <div class="mt-8 bg-gray-800/50 border border-gray-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">{{ __('messages.forum_write_reply') }}</h3>
                <form method="POST" action="{{ route('forum.store-post', [$category, $thread]) }}">
                    @csrf
                    @include("forum.partials.bbcode-toolbar")
                    @error('body')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end mt-4">
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                            <i class="fas fa-paper-plane mr-2"></i>{{ __('messages.forum_reply') }}
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="mt-8 bg-gray-800/50 border border-yellow-600/30 rounded-lg px-6 py-4 text-center text-yellow-400">
                <i class="fas fa-lock mr-2"></i> {{ __('messages.forum_thread_locked') }}
            </div>
            @endunless
        @else
            <div class="mt-8 bg-gray-800/50 border border-gray-700 rounded-lg px-6 py-4 text-center text-gray-400">
                <a href="{{ route('login') }}" class="text-blue-400 hover:text-blue-300">{{ __('messages.forum_login_to_reply') }}</a>
                {{ __('messages.forum_login_to_reply_suffix') }}
            </div>
        @endauth
    </div>
</x-app-layout>
