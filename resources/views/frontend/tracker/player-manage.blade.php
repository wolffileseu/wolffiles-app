<x-layouts.app :title="'Manage ' . ($player->display_name ?: $player->name_clean ?: $player->name)">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('tracker.player.show', $player) }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; View public profile</a>
        <div class="flex items-center gap-3 mt-1">
            <h1 class="text-2xl font-bold text-white">{{ $player->display_name ?: $player->name_clean ?: $player->name }}</h1>
            <span class="text-gray-500">&middot; Manage</span>
        </div>
        <div class="text-xs text-gray-500 font-mono mt-1">Player #{{ $player->id }}</div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-900/30 border border-green-700 text-green-300 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-700 text-red-300 rounded-lg text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-700 text-red-300 rounded-lg text-sm">
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tracker.player.manage.profile', $player) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Profile section --}}
        <div class="bg-gray-800/50 border border-gray-700 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700">
                <h2 class="text-white font-semibold text-sm uppercase tracking-wide">Profile</h2>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Display Name (Optional)</label>
                    <input name="display_name" value="{{ old('display_name', $player->display_name) }}" maxlength="100" placeholder="Defaults to in-game name" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    <p class="text-xs text-gray-500 mt-1">In-game name: <span class="font-mono text-amber-400">{{ $player->name_clean ?? $player->name }}</span></p>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Tagline</label>
                    <input name="tagline" value="{{ old('tagline', $player->tagline) }}" maxlength="200" placeholder="One-line description (e.g. 'Veteran ETPub regular')" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Bio (Markdown + BBCode)</label>
                    <x-bbcode-toolbar target="player-bio-editor" />
                    <textarea id="player-bio-editor" name="bio" rows="8" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-b-lg text-sm font-mono focus:outline-none focus:border-amber-500">{{ old('bio', $player->bio) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Branding section --}}
        <div class="bg-gray-800/50 border border-gray-700 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700">
                <h2 class="text-white font-semibold text-sm uppercase tracking-wide">Branding</h2>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Avatar URL</label>
                    <input name="avatar_url" value="{{ old('avatar_url', $player->avatar_url) }}" placeholder="https://..." class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">
                    @if($player->avatar_url)
                        <img src="{{ $player->avatar_url }}" class="w-16 h-16 rounded-full mt-2 border border-gray-700 object-cover" alt="Avatar preview">
                    @endif
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Banner URL</label>
                    <input name="banner_url" value="{{ old('banner_url', $player->banner_url) }}" placeholder="https://..." class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">
                    @if($player->banner_url)
                        <img src="{{ $player->banner_url }}" class="w-full max-h-24 mt-2 rounded border border-gray-700 object-cover" alt="Banner preview">
                    @endif
                </div>
            </div>
        </div>

        {{-- Social links --}}
        <div class="bg-gray-800/50 border border-gray-700 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700">
                <h2 class="text-white font-semibold text-sm uppercase tracking-wide">Social Links</h2>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5"><i class="fab fa-youtube text-red-500 mr-1"></i>YouTube</label>
                    <input name="youtube_url" value="{{ old('youtube_url', $player->youtube_url) }}" placeholder="https://youtube.com/@yourchannel" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5"><i class="fab fa-twitch text-purple-500 mr-1"></i>Twitch</label>
                    <input name="twitch_url" value="{{ old('twitch_url', $player->twitch_url) }}" placeholder="https://twitch.tv/yourname" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5"><i class="fab fa-discord text-indigo-400 mr-1"></i>Discord</label>
                    <input name="discord_url" value="{{ old('discord_url', $player->discord_url) }}" placeholder="username or invite URL" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5"><i class="fab fa-twitter text-blue-400 mr-1"></i>Twitter / X</label>
                    <input name="twitter_url" value="{{ old('twitter_url', $player->twitter_url) }}" placeholder="https://x.com/yourname" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5"><i class="fas fa-globe text-gray-400 mr-1"></i>Website</label>
                    <input name="website_url" value="{{ old('website_url', $player->website_url) }}" placeholder="https://yoursite.com" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('profile.show', auth()->user()) }}" class="text-sm text-gray-400 hover:text-gray-200">&larr; Back to your user profile</a>
            <button type="submit" class="px-6 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm transition">Save Changes</button>
        </div>

    </form>
</div>
</x-layouts.app>
