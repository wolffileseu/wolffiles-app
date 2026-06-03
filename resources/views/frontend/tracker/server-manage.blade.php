<x-layouts.app :title="$server->hostname_clean . ' — Manage'">
<div class="max-w-6xl mx-auto px-4 py-6" x-data="{ tab: 'content' }">

    <div class="flex items-center justify-between mb-5 gap-3 flex-wrap">
        <div>
            <a href="{{ route('tracker.server.show', $server) }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; View public page</a>
            <h1 class="text-2xl font-bold text-white mt-1">{!! $server->hostname_html ?? e($server->hostname_clean) !!} <span class="text-gray-500 text-base font-normal">&middot; Manage</span></h1>
            <div class="text-xs text-gray-500 font-mono">{{ $server->ip }}:{{ $server->port }}</div>
        </div>
    </div>

    @if(session('success'))<div class="mb-4 px-4 py-3 bg-green-900/30 border border-green-500/30 text-green-300 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="flex border-b border-gray-700 mb-5 overflow-x-auto">
        <button @click="tab='content'" :class="tab==='content' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Content</button>
        <button @click="tab='clan'" :class="tab==='clan' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Clan Link</button>
        <button @click="tab='stats'" :class="tab==='stats' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Stats</button>
    </div>

    {{-- ============ CONTENT ============ --}}
    <div x-show="tab==='content'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('server.manage.content', $server) }}" class="lg:col-span-2 space-y-5">
            @csrf @method('PUT')
            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Public URL</h3>
                @php $slugLocked = $server->slug_changed_at && $server->slug_changed_at->diffInDays(now()) < 30; $daysLeft = $slugLocked ? 30 - (int) $server->slug_changed_at->diffInDays(now()) : 0; @endphp
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">URL Slug (optional)</label>
                    <div class="flex items-center gap-1">
                        <span class="text-gray-500 text-sm font-mono">/servers/</span>
                        <input name="slug" value="{{ old('slug', $server->slug) }}" {{ $slugLocked ? 'disabled' : '' }} pattern="^[a-z][a-z0-9-]+$" placeholder="e.g. baroga-etpub" class="flex-1 bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500 {{ $slugLocked ? 'opacity-50 cursor-not-allowed' : '' }}">
                    </div>
                    <p class="mt-1 text-xs {{ $slugLocked ? 'text-amber-400' : 'text-gray-500' }}">
                        @if($slugLocked)
                            🔒 Slug change locked for {{ $daysLeft }} more day(s). Last changed {{ $server->slug_changed_at->diffForHumans() }}.
                        @else
                            Optional custom URL. Leave blank to use server ID. After change, locked for 30 days.
                            @if($server->slug)<br>Current: <span class="font-mono text-amber-400">wolffiles.eu/servers/{{ $server->slug }}</span>@endif
                        @endif
                    </p>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Page Content</h3>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Description (Markdown + BBCode)</label>
                    <textarea name="description" rows="5" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">{{ old('description', $server->description) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Server Rules (Markdown + BBCode)</label>
                    <textarea name="rules" rows="6" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">{{ old('rules', $server->rules) }}</textarea>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Branding</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Logo URL</label>
                        <input name="server_logo_url" value="{{ old('server_logo_url', $server->server_logo_url) }}" placeholder="https://..." class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Banner URL</label>
                        <input name="server_banner_url" value="{{ old('server_banner_url', $server->server_banner_url) }}" placeholder="https://..." class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Contact &amp; Links</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Website</label>
                        <input name="server_website" value="{{ old('server_website', $server->server_website) }}" placeholder="https://" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Discord</label>
                        <input name="server_discord" value="{{ old('server_discord', $server->server_discord) }}" placeholder="discord.gg/..." class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Email</label>
                        <input name="server_email" type="email" value="{{ old('server_email', $server->server_email) }}" placeholder="contact@..." class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                    </div>
                </div>
            </div>

            <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">Save changes</button>
        </form>

        <div class="space-y-4">
            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
                <h4 class="text-white font-semibold text-sm uppercase tracking-wide mb-2">Logo Preview</h4>
                @if($server->server_logo_url)
                <img src="{{ $server->server_logo_url }}" alt="logo" class="w-32 h-32 object-contain bg-gray-900 rounded">
                @else
                <div class="w-32 h-32 bg-gray-900 rounded flex items-center justify-center text-gray-600 text-xs">No logo</div>
                @endif
            </div>
            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
                <h4 class="text-white font-semibold text-sm uppercase tracking-wide mb-2">Banner Preview</h4>
                @if($server->server_banner_url)
                <img src="{{ $server->server_banner_url }}" alt="banner" class="w-full h-24 object-cover bg-gray-900 rounded">
                @else
                <div class="w-full h-24 bg-gray-900 rounded flex items-center justify-center text-gray-600 text-xs">No banner</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============ CLAN LINK ============ --}}
    <div x-show="tab==='clan'" x-cloak class="space-y-5">
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Currently linked clan</h3>
            @if($server->claimed_by_clan_id)
                @php $linkedClan = \App\Models\Clan::find($server->claimed_by_clan_id); @endphp
                <div class="flex items-center justify-between gap-4 bg-gray-900 border border-gray-700 rounded-lg p-4">
                    <div>
                        <div class="text-amber-400 font-mono">{{ $linkedClan?->display_tag ?? '[?]' }} {{ $linkedClan?->name ?? 'Unknown' }}</div>
                        <div class="text-xs text-gray-500">Slug: {{ $linkedClan?->slug ?? '—' }}</div>
                    </div>
                    <form method="POST" action="{{ route('server.manage.unlink-clan', $server) }}" onsubmit="return confirm('Unlink server from this clan?')">
                        @csrf
                        <button class="px-4 py-2 bg-red-900/30 border border-red-500/30 text-red-400 hover:bg-red-900/50 rounded-lg text-sm">Unlink</button>
                    </form>
                </div>
            @else
                <p class="text-gray-500 text-sm">This server is not linked to any clan.</p>
            @endif
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Link to a clan</h3>
            @if($userManagedClans->isEmpty())
            <p class="text-gray-500 text-sm">You are not an owner or admin of any registered clan. Claim a clan first.</p>
            @else
            <form method="POST" action="{{ route('server.manage.link-clan', $server) }}" class="flex gap-2 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Choose a clan</label>
                    <select name="clan_id" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                        @foreach($userManagedClans as $c)
                        <option value="{{ $c->id }}" {{ $server->claimed_by_clan_id === $c->id ? 'selected' : '' }}>{{ $c->display_tag }} {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">Link</button>
            </form>
            <p class="mt-2 text-xs text-gray-500">Linking a server makes it visible on that clan's public page.</p>
            @endif
        </div>
    </div>

    {{-- ============ STATS ============ --}}
    <div x-show="tab==='stats'" x-cloak class="space-y-5">
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Server statistics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><div class="text-gray-500 text-xs uppercase">Players tracked</div><div class="text-white text-lg font-mono">{{ number_format($server->total_players_tracked ?? 0) }}</div></div>
                <div><div class="text-gray-500 text-xs uppercase">Unique players</div><div class="text-white text-lg font-mono">{{ number_format($server->total_unique_players ?? 0) }}</div></div>
                <div><div class="text-gray-500 text-xs uppercase">Uptime %</div><div class="text-white text-lg font-mono">{{ $server->uptime_percentage }}%</div></div>
                <div><div class="text-gray-500 text-xs uppercase">Status</div><div class="text-white text-lg font-mono">{{ $server->is_online ? 'online' : 'offline' }}</div></div>
                <div><div class="text-gray-500 text-xs uppercase">Current map</div><div class="text-white text-sm font-mono">{{ $server->current_map ?? '—' }}</div></div>
                <div><div class="text-gray-500 text-xs uppercase">Mod</div><div class="text-white text-sm font-mono">{{ $server->mod_name ?? '—' }}</div></div>
                <div><div class="text-gray-500 text-xs uppercase">First seen</div><div class="text-white text-xs">{{ $server->first_seen_at?->diffForHumans() ?? '—' }}</div></div>
                <div><div class="text-gray-500 text-xs uppercase">Last seen</div><div class="text-white text-xs">{{ $server->last_seen_at?->diffForHumans() ?? '—' }}</div></div>
            </div>
        </div>
    </div>

</div>
</x-layouts.app>
