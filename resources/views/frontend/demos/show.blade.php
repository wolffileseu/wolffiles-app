<x-layouts.app :title="$demo->title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center space-x-2 text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">Home</a>
            <span>/</span>
            <a href="{{ route('demos.index') }}" class="hover:text-amber-400">Demos</a>
            @if($demo->category?->parent)
                <span>/</span>
                <a href="{{ route('demos.index', ['category' => $demo->category->parent->slug]) }}" class="hover:text-amber-400">{{ $demo->category->parent->name }}</a>
            @endif
            @if($demo->category)
                <span>/</span>
                <a href="{{ route('demos.index', ['category' => $demo->category->slug]) }}" class="hover:text-amber-400">{{ $demo->category->name }}</a>
            @endif
            <span>/</span>
            <span class="text-gray-300">{{ Str::limit($demo->title, 40) }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Title & Match --}}
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <div class="flex items-start justify-between mb-3">
                        <h1 class="text-2xl font-bold text-white">{{ $demo->title }}</h1>
                        <span class="bg-blue-600/20 text-blue-400 px-3 py-1 rounded text-sm font-medium">{{ $demo->game }}</span>
                    </div>

                    @if($demo->team_axis && $demo->team_allies)
                        <div class="bg-gray-900 rounded-lg p-4 mb-4 text-center">
                            <div class="text-xl font-bold">
                                <span class="text-red-400">{{ $demo->team_axis }}</span>
                                <span class="text-gray-500 mx-3">vs</span>
                                <span class="text-blue-400">{{ $demo->team_allies }}</span>
                            </div>
                            <div class="flex items-center justify-center space-x-4 mt-2 text-sm text-gray-400">
                                @if($demo->map_name)<span>🗺️ {{ $demo->map_name }}</span>@endif
                                @if($demo->gametype)<span>{{ $demo->gametype }}</span>@endif
                                @if($demo->match_format)<span>{{ $demo->match_format }}</span>@endif
                                @if($demo->match_date)<span>{{ $demo->match_date->format('d.m.Y') }}</span>@endif
                            </div>
                        </div>
                    @endif

                    @if($demo->description)
                        <div class="prose prose-invert max-w-none text-gray-300">
                            {!! nl2br(e($demo->description)) !!}
                        </div>
                    @endif

                    {{-- Tags --}}
                    @if($demo->tags->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-700">
                            @foreach($demo->tags as $tag)
                                <a href="{{ route('demos.index', ['tag' => $tag->slug]) }}" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-3 py-1 rounded-full text-xs transition-colors">{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Player List (parsed from demo) --}}
                @if($demo->player_list && count($demo->player_list) > 0)
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">👥 Players (extracted from demo)</h2>
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Axis --}}
                            <div>
                                <h3 class="text-red-400 font-semibold text-sm mb-2 uppercase tracking-wider">Axis</h3>
                                <div class="space-y-1">
                                    @foreach(collect($demo->player_list)->where('team', 'axis') as $player)
                                        <div class="bg-red-900/20 border border-red-900/30 rounded px-3 py-1.5 text-sm text-gray-200">
                                            {{ $player['name'] }}
                                        </div>
                                    @endforeach
                                    @if(collect($demo->player_list)->where('team', 'axis')->isEmpty())
                                        <span class="text-gray-500 text-sm">—</span>
                                    @endif
                                </div>
                            </div>
                            {{-- Allies --}}
                            <div>
                                <h3 class="text-blue-400 font-semibold text-sm mb-2 uppercase tracking-wider">Allies</h3>
                                <div class="space-y-1">
                                    @foreach(collect($demo->player_list)->where('team', 'allies') as $player)
                                        <div class="bg-blue-900/20 border border-blue-900/30 rounded px-3 py-1.5 text-sm text-gray-200">
                                            {{ $player['name'] }}
                                        </div>
                                    @endforeach
                                    @if(collect($demo->player_list)->where('team', 'allies')->isEmpty())
                                        <span class="text-gray-500 text-sm">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- Spectators --}}
                        @if(collect($demo->player_list)->where('team', 'spectator')->isNotEmpty())
                            <div class="mt-3 pt-3 border-t border-gray-700">
                                <span class="text-gray-500 text-xs">Spectators: </span>
                                <span class="text-gray-400 text-xs">
                                    {{ collect($demo->player_list)->where('team', 'spectator')->pluck('name')->implode(', ') }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Screenshots --}}
                @if($demo->screenshots->isNotEmpty())
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">Screenshots</h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($demo->screenshots as $ss)
                                @php try { $ssUrl = Storage::disk('s3')->temporaryUrl($ss->path, now()->addHours(2)); } catch (\Exception $e) { $ssUrl = null; } @endphp
                                @if($ssUrl)
                                    <img src="{{ $ssUrl }}" alt="Screenshot" class="rounded-lg w-full h-32 object-cover cursor-pointer hover:opacity-80 transition-opacity">
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Comments --}}
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">Comments ({{ $demo->comments->count() }})</h2>
                    @forelse($demo->comments as $comment)
                        <div class="flex space-x-3 mb-4 pb-4 border-b border-gray-700 last:border-0">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-amber-400 font-medium text-sm">{{ $comment->user?->name ?? 'Unknown' }}</span>
                                    <span class="text-gray-500 text-xs">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-gray-300 text-sm">{{ $comment->body }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No comments yet.</p>
                    @endforelse

                    @auth
                        <form method="POST" action="{{ route('comments.store') }}" class="mt-4 pt-4 border-t border-gray-700">
                            @csrf
                            <input type="hidden" name="commentable_type" value="App\Models\Demo">
                            <input type="hidden" name="commentable_id" value="{{ $demo->id }}">
                            <textarea name="body" rows="3" placeholder="Write a comment..."
                                      class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-4 py-2 text-sm" required></textarea>
                            <button class="mt-2 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Post Comment</button>
                        </form>
                    @endauth
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Download Box --}}
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <a href="{{ route('demos.download', $demo) }}"
                       class="block w-full bg-amber-600 hover:bg-amber-700 text-white text-center py-3 rounded-lg font-bold text-lg transition-colors mb-4">
                        ↓ Download Demo
                    </a>
                    <div class="text-center text-gray-400 text-sm">
                        {{ $demo->file_size_formatted }} · {{ $demo->file_name }}
                    </div>
                </div>

                {{-- Demo Info --}}
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-white font-semibold mb-4">Demo Info</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Game</dt>
                            <dd class="text-white">{{ $demo->game_label }}</dd>
                        </div>
                        @if($demo->map_name)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Map</dt>
                                <dd class="text-amber-400 font-medium">{{ $demo->map_name }}</dd>
                            </div>
                        @endif
                        @if($demo->mod_name)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Mod</dt>
                                <dd class="text-white">{{ $demo->mod_name }}</dd>
                            </div>
                        @endif
                        @if($demo->gametype)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Gametype</dt>
                                <dd class="text-white">{{ $demo->gametype }}</dd>
                            </div>
                        @endif
                        @if($demo->match_format)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Format</dt>
                                <dd class="text-white">{{ $demo->match_format }}</dd>
                            </div>
                        @endif
                        @if($demo->demo_format)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Demo Format</dt>
                                <dd class="text-amber-400 font-medium">{{ $demo->format_badge }}</dd>
                            </div>
                        @endif
                        @if($demo->duration_formatted)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Duration (est.)</dt>
                                <dd class="text-white">~{{ $demo->duration_formatted }}</dd>
                            </div>
                        @endif
                        @if($demo->server_name)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Server</dt>
                                <dd class="text-white text-right text-xs max-w-[160px] truncate" title="{{ $demo->server_name }}">{{ $demo->server_name }}</dd>
                            </div>
                        @endif
                        @if($demo->recorder_name)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Recorded by</dt>
                                <dd class="text-white">{{ $demo->recorder_name }}</dd>
                            </div>
                        @endif
                        @if($demo->match_source)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Source</dt>
                                <dd>
                                    @if($demo->match_source_url)
                                        <a href="{{ $demo->match_source_url }}" target="_blank" rel="noopener" class="text-amber-400 hover:underline">{{ $demo->match_source }} ↗</a>
                                    @else
                                        <span class="text-white">{{ $demo->match_source }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endif
                        @if($demo->match_date)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Match Date</dt>
                                <dd class="text-white">{{ $demo->match_date->format('d.m.Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Stats --}}
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-white font-semibold mb-4">Stats</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Downloads</dt>
                            <dd class="text-white">{{ number_format($demo->download_count) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Views</dt>
                            <dd class="text-white">{{ number_format($demo->view_count) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Uploaded</dt>
                            <dd class="text-white">{{ $demo->created_at->format('d.m.Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Uploader</dt>
                            <dd><a href="{{ route('profile.show', $demo->user) }}" class="text-amber-400 hover:underline">{{ $demo->user?->name }}</a></dd>
                        </div>
                    </dl>
                </div>

                {{-- Related --}}
                @if($related->isNotEmpty())
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                        <h3 class="text-white font-semibold mb-4">Related Demos</h3>
                        <div class="space-y-3">
                            @foreach($related as $rel)
                                <a href="{{ route('demos.show', $rel) }}" class="block p-3 bg-gray-900 rounded-lg hover:bg-gray-700 transition-colors">
                                    <div class="text-white text-sm font-medium line-clamp-1">{{ $rel->title }}</div>
                                    <div class="text-gray-500 text-xs mt-1">{{ $rel->map_name }} · {{ $rel->game }} · ↓ {{ $rel->download_count }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
