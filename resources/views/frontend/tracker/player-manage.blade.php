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
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Country</label>
                    <div class="flex items-center gap-2">
                        @if($player->country_code)
                            <img src="https://flagcdn.com/{{ strtolower($player->country_code) }}.svg" class="w-6 h-4 rounded-sm flex-shrink-0" alt="{{ $player->country_code }}">
                        @endif
                        <select name="country_code" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                            <option value="">— None —</option>
                            @foreach(\App\Helpers\CountryList::codes() as $code => $name)
                                <option value="{{ $code }}" @selected(old('country_code', $player->country_code) === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($player->country_locked)
                        <p class="text-xs text-amber-400 mt-1">🔒 Locked. Auto-detection won't override your choice.</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Country is auto-detected from your in-game IP. Set it manually to lock.</p>
                    @endif
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

    {{-- ============================================================
         SCREENSHOTS SECTION
    ============================================================ --}}
    @php
        $screenshots = $player->screenshots()->get();
        $maxScreenshots = \App\Http\Controllers\Frontend\PlayerScreenshotController::MAX_SCREENSHOTS_PER_PLAYER;
        $screenshotsCount = $screenshots->count();
        $canUpload = $screenshotsCount < $maxScreenshots;
    @endphp
    <div class="mt-8 bg-gray-800/50 border border-gray-700 rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700 flex items-center justify-between">
            <h2 class="text-white font-semibold text-sm uppercase tracking-wide">
                <i class="fas fa-images mr-2 text-amber-400"></i>Screenshots
            </h2>
            <span class="text-xs text-gray-500 font-mono">{{ $screenshotsCount }} / {{ $maxScreenshots }}</span>
        </div>
        <div class="p-4">

            {{-- Upload form --}}
            @if($canUpload)
            <form method="POST" action="{{ route('tracker.player.screenshot.upload', $player) }}" enctype="multipart/form-data" class="mb-6">
                @csrf
                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Upload Screenshots</label>
                <input type="file" name="screenshots[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" required
                       class="block w-full text-sm text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-500 file:text-gray-900 hover:file:bg-amber-400 file:cursor-pointer cursor-pointer bg-gray-900 border border-gray-600 rounded-lg">
                <p class="text-xs text-gray-500 mt-1">Max 6 files per upload. Max 5 MB each. JPG, PNG, WebP, GIF. Up to {{ $maxScreenshots - $screenshotsCount }} more allowed.</p>
                <button type="submit" class="mt-3 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm transition">Upload</button>
            </form>
            @else
            <div class="mb-6 px-4 py-3 bg-amber-900/20 border border-amber-700 text-amber-300 rounded-lg text-sm">
                You have reached the limit of {{ $maxScreenshots }} screenshots. Delete one to upload more.
            </div>
            @endif

            {{-- Existing screenshots grid --}}
            @if($screenshotsCount > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($screenshots as $s)
                <div class="relative group bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                    <a href="{{ $s->url }}" target="_blank" rel="noopener" class="block aspect-video bg-black">
                        <img src="{{ $s->url }}" class="w-full h-full object-contain" alt="{{ $s->title ?: 'Screenshot' }}" loading="lazy">
                    </a>
                    <div class="p-2 text-xs text-gray-400">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono">{{ $s->size_formatted }} @if($s->width && $s->height)&middot; {{ $s->width }}&times;{{ $s->height }}@endif</span>
                            <form method="POST" action="{{ route('tracker.player.screenshot.destroy', [$player, $s]) }}" onsubmit="return confirm('Delete this screenshot?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-300" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        @if(!$s->is_public)
                            <div class="text-amber-400 mt-1">🔒 Private</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center text-sm text-gray-500 py-8">No screenshots yet. Upload one above!</div>
            @endif
        </div>
    </div>
</div>
</x-layouts.app>
