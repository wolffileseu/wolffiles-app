<x-layouts.app :title="$luaScript->title">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">Home</a> /
            <a href="{{ route('lua.index') }}" class="hover:text-amber-400">LUA Scripts</a> /
            <span class="text-gray-300">{{ $luaScript->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <h1 class="text-3xl font-bold text-white mb-4">{{ $luaScript->title }}</h1>

                @if($luaScript->compatible_mods)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($luaScript->compatible_mods as $mod)
                            <span class="bg-amber-600/20 text-amber-400 px-3 py-1 rounded-full text-sm">{{ $mod }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6 prose prose-invert max-w-none">
                    {!! $luaScript->description_html ?? nl2br(e($luaScript->description)) !!}
                </div>

                @if($luaScript->installation_guide)
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 mb-6">
                        <h3 class="text-lg font-semibold text-amber-400 mb-4">Installation Guide</h3>
                        <div class="prose prose-invert max-w-none">
                            {!! nl2br(e($luaScript->installation_guide)) !!}
                        </div>
                    </div>
                @endif

                {{-- Comments --}}
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Comments ({{ $luaScript->comments->count() }})</h3>
                    @foreach($luaScript->comments as $comment)
                        <div class="border-b border-gray-700 py-4 last:border-0">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="font-medium text-amber-400">{{ $comment->user?->name }}</span>
                                <span class="text-gray-500 text-xs">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-300">{{ $comment->body }}</p>
                        </div>
                    @endforeach

                    @auth
                        <form method="POST" action="{{ route('comments.store') }}" class="mt-4">
                            @csrf
                            <input type="hidden" name="commentable_type" value="lua_script">
                            <input type="hidden" name="commentable_id" value="{{ $luaScript->id }}">
                            <textarea name="body" rows="3" placeholder="Write a comment..."
                                      class="w-full bg-gray-700 border-gray-600 text-white rounded-lg p-3"></textarea>
                            <button class="mt-2 bg-amber-600 hover:bg-amber-700 text-white px-6 py-2 rounded-lg text-sm">Post Comment</button>
                        </form>
                    @endauth
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <a href="{{ route('lua.download', $luaScript) }}"
                       class="block w-full bg-amber-600 hover:bg-amber-700 text-white text-center py-4 rounded-lg font-bold text-lg transition-colors mb-4">
                        ↓ Download ({{ $luaScript->file_size_formatted }})
                    </a>

                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Version</dt>
                            <dd class="text-gray-200">{{ $luaScript->version ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Author</dt>
                            <dd><a href="{{ route('profile.show', $luaScript->user) }}" class="text-amber-400 hover:underline">{{ $luaScript->user?->name }}</a></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Downloads</dt>
                            <dd class="text-gray-200">{{ number_format($luaScript->download_count) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Published</dt>
                            <dd class="text-gray-200">{{ $luaScript->published_at?->format('d.m.Y') }}</dd>
                        </div>
                        @if($luaScript->min_lua_version)
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Min. LUA Version</dt>
                            <dd class="text-gray-200">{{ $luaScript->min_lua_version }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
