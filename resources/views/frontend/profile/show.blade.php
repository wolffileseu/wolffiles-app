<x-layouts.app :title="$user->name">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Profile Header --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-8 mb-8">
            <div class="flex items-center space-x-6">
                @php
                    $avatarUrl = $user->avatar_url ?? null;
                    if ($avatarUrl && !Str::startsWith($avatarUrl, ['http://', 'https://'])) {
                        $avatarUrl = Storage::disk('s3')->url($avatarUrl);
                    }
                @endphp
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" class="w-20 h-20 rounded-full" onerror="this.style.display='none'">
                @else
                    <div class="w-20 h-20 rounded-full bg-amber-600 flex items-center justify-center text-white text-2xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ $user->name }}</h1>
                    @if($user->bio)
                        <p class="text-gray-400 mt-1">{{ $user->bio }}</p>
                    @endif
                    <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                        <span>{{ __('messages.member_since') ?? 'Member since' }} {{ $user->created_at->format('M Y') }}</span>
                        <span>{{ $user->files()->where('status', 'approved')->count() }} Uploads</span>
                        @if($user->website)
                            <a href="{{ $user->website }}" target="_blank" class="text-amber-400 hover:underline">Website</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Badges --}}
            @if($user->badges && $user->badges->isNotEmpty())
                <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-700">
                    @foreach($user->badges as $badge)
                        <span class="px-3 py-1 rounded-full text-sm font-medium"
                              style="background-color: {{ $badge->color }}20; color: {{ $badge->color }};"
                              title="{{ $badge->description }}">
                            @if($badge->icon)
                                <span class="mr-1">{!! $badge->icon !!}</span>
                            @endif
                            {{ $badge->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Uploads --}}
        <h2 class="text-2xl font-bold text-white mb-6">Uploads ({{ $uploads->total() }})</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($uploads as $file)
                @include('components.file-card', ['file' => $file])
            @empty
                <p class="col-span-full text-gray-400">{{ __('messages.no_uploads') ?? 'No uploads yet.' }}</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $uploads->links() }}</div>

        {{-- LUA Scripts --}}
        @if($luaScripts->isNotEmpty())
            <h2 class="text-2xl font-bold text-white mt-12 mb-6">LUA Scripts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($luaScripts as $script)
                    <a href="{{ route('lua.show', $script) }}" class="block bg-gray-800 rounded-lg border border-gray-700 p-4 hover:border-amber-600 transition-colors">
                        <h3 class="text-white font-semibold">{{ $script->title }}</h3>
                        <p class="text-gray-400 text-sm mt-1">↓ {{ number_format($script->download_count) }} Downloads</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
