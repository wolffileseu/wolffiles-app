<x-layouts.app :title="'Manage [' . $clan->tag . '] ' . $clan->name">
@php
    $tc = $clan->trackerClan;
    $isOwner = $manager->role === 'leader';
    $isAdmin = in_array($manager->role, ['leader','owner']);
    $roleLabels = ['Leader','Co-Leader','Recruiter','Member','Trial','Inactive'];
@endphp

<div class="max-w-7xl mx-auto px-4 py-8" x-data="{ tab: 'content' }">

    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div>
            <a href="{{ $tc ? route('tracker.clan.show', $tc) : '#' }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; View public page</a>
            <h1 class="text-2xl font-bold text-white mt-1"><span class="text-amber-500">{{ $clan->display_tag }}</span> {{ $clan->name }} <span class="text-gray-500 text-base font-normal">&middot; Manage</span></h1>
        </div>
        <span class="px-3 py-1.5 bg-gray-700 text-amber-400 rounded-lg text-xs uppercase tracking-wide border border-amber-500/30">Your role: {{ $manager->role }}</span>
    </div>

    @if(session('success'))<div class="mb-4 px-4 py-3 bg-green-900/30 border border-green-500/30 text-green-300 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm">{{ $errors->first() }}</div>@endif

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-gray-700 mb-6 flex-wrap">
        <button @click="tab='content'" :class="tab==='content' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Content</button>
        <button @click="tab='members'" :class="tab==='members' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Members<span class="text-gray-600 ml-1">{{ $members->count() }}</span></button>
        <button @click="tab='servers'" :class="tab==='servers' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Servers<span class="text-gray-600 ml-1">{{ $claimedServers->count() + $autoDetectedServers->count() }}</span></button>
        <button @click="tab='news'" :class="tab==='news' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">News<span class="text-gray-600 ml-1">{{ $news->count() }}</span></button>
        @if($isAdmin)
        <button @click="tab='managers'" :class="tab==='managers' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Managers<span class="text-gray-600 ml-1">{{ $clan->managers->count() }}</span></button>
        <button @click="tab='apps'" :class="tab==='apps' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Applications<span class="text-gray-600 ml-1">{{ $applications->where('status','pending')->count() }}</span></button>
        @endif
        @if($manager->role === 'leader')
        <button @click="tab='api'" :class="tab==='api' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">API Keys<span class="text-gray-600 ml-1">{{ $apiKeys->count() }}</span></button>
        @endif
    </div>

    {{-- ========================= CONTENT ========================= --}}
    <div x-show="tab==='content'">
        <form method="POST" action="{{ route('clan.manage.content', $clan->tracker_clan_id) }}" class="space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-5">
                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Clan Name</label>
                                <input name="name" value="{{ old('name', $clan->name) }}" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Tag Display</label>
                                <input name="tag_display" value="{{ old('tag_display', $clan->tag_display) }}" placeholder="[RoG] / =RoG= / .RoG" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500">
                                <p class="mt-1 text-xs text-gray-500">Wie soll dein Clan-Tag aussehen? z.B. [RoG], =RoG=, .RoG. Leer = Standard [tag].</p>
                            </div>
                        </div>
                        @php $slugLocked = $clan->slug_changed_at && $clan->slug_changed_at->diffInDays(now()) < 30; $daysLeft = $slugLocked ? 30 - (int) $clan->slug_changed_at->diffInDays(now()) : 0; @endphp
                        <div>
                            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Public URL Slug</label>
                            <div class="flex items-center gap-1">
                                <span class="text-gray-500 text-sm font-mono">/clan/</span>
                                <input name="slug" value="{{ old('slug', $clan->slug) }}" required {{ $slugLocked ? 'readonly' : '' }} pattern="^[a-z][a-z0-9-]+$" placeholder="rog" class="flex-1 bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500 {{ $slugLocked ? 'opacity-50 cursor-not-allowed' : '' }}">
                            </div>
                            <p class="mt-1 text-xs {{ $slugLocked ? 'text-amber-400' : 'text-gray-500' }}">
                                @if($slugLocked)
                                    🔒 Slug change locked for {{ $daysLeft }} more day(s). Last changed {{ $clan->slug_changed_at->diffForHumans() }}.
                                @else
                                    Public URL: <span class="font-mono text-amber-400">wolffiles.eu/clan/{{ $clan->slug ?: 'your-slug' }}</span> — lowercase letters, numbers, dashes only. After change, locked for 30 days.
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">About (Markdown + BBCode)</label>
                            <x-bbcode-toolbar target="clan-description-editor" />
                            <textarea id="clan-description-editor" name="description" rows="6" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-b-lg text-sm font-mono focus:outline-none focus:border-amber-500">{{ old('description', $clan->description) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Server Rules</label>
                            <x-bbcode-toolbar target="clan-rules-editor" />
                            <textarea id="clan-rules-editor" name="rules" rows="5" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-b-lg text-sm font-mono focus:outline-none focus:border-amber-500">{{ old('rules', $clan->rules) }}</textarea>
                        </div>
                    </div>

                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                        <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Recruitment</h3>
                        <label class="flex items-center gap-2 text-sm text-gray-300">
                            <input type="checkbox" name="is_recruiting" value="1" {{ old('is_recruiting', $clan->is_recruiting) ? 'checked' : '' }} class="rounded bg-gray-900 border-gray-600 text-amber-500">
                            We are recruiting
                        </label>
                        <div>
                            <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Requirements / Summary</label>
                            <textarea name="recruitment_summary" rows="4" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">{{ old('recruitment_summary', $clan->recruitment_summary) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                        <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Info & Links</h3>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Location</label><input name="location" value="{{ old('location', $clan->location) }}" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Founded</label><input name="founded" value="{{ old('founded', $clan->founded) }}" placeholder="e.g. 2008" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Website</label><input name="website" value="{{ old('website', $clan->website) }}" placeholder="https://" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Discord</label><input name="contact_discord" value="{{ old('contact_discord', $clan->contact_discord) }}" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">TS / Vent</label><input name="ts_address" value="{{ old('ts_address', $clan->ts_address) }}" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                    </div>
                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4">
                        <h3 class="text-white font-semibold text-sm uppercase tracking-wide">Images (URL)</h3>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Logo URL</label><input name="logo" value="{{ old('logo', $clan->logo) }}" placeholder="https://" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                        <div><label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Banner URL</label><input name="banner" value="{{ old('banner', $clan->banner) }}" placeholder="https://" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500"></div>
                    </div>
                    <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
                        <label class="flex items-center gap-2 text-sm text-gray-300">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $clan->is_published) ? 'checked' : '' }} class="rounded bg-gray-900 border-gray-600 text-amber-500">
                            Page published (visible to public)
                        </label>
                    </div>
                </div>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm transition">Save Changes</button>
        </form>

        {{-- Auto-Join toggle (owner only, separate form) --}}
        @if($clan->trackerClan && $manager->role === \App\Models\ClanManager::ROLE_LEADER)
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 mt-5">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Auto-Join Detection</h3>
            <form method="POST" action="{{ route("clan.manage.auto_join", $clan->tracker_clan_id) }}" class="flex items-start gap-3">
                @csrf
                <input type="hidden" name="auto_join_enabled" value="0">
                <input type="checkbox" name="auto_join_enabled" value="1" onchange="this.form.submit()" {{ $clan->trackerClan->auto_join_enabled ? 'checked' : '' }} class="rounded bg-gray-900 border-gray-600 text-amber-500 mt-0.5">
                <div class="text-sm text-gray-300">
                    Auto-add players whose in-game name contains the clan tag <span class="font-mono text-amber-400">{{ $clan->display_tag }}</span>.
                    <div class="text-xs text-gray-500 mt-1">When off, members can only be added manually. Recommended off to keep fake-tag players out.</div>
                </div>
            </form>
        </div>
        @endif
    </div>

    {{-- ========================= MEMBERS ========================= --}}
    <div x-show="tab==='members'" x-cloak class="space-y-5">
        @if(!$tc)
        <div class="bg-amber-900/10 border border-amber-500/20 rounded-lg p-4 text-sm text-gray-400">No tracker clan linked — members are auto-detected once players with the tag are seen.</div>
        @else
        @if($isAdmin)
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Squads</h3>
            <div class="flex flex-wrap gap-2 mb-3">
                @forelse($squads as $squad)
                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-900 border border-gray-600 rounded-lg text-sm text-gray-300">
                    {{ $squad->name }}
                    <form method="POST" action="{{ route('clan.manage.squad.delete', [$clan->tracker_clan_id, $squad->id]) }}" onsubmit="return confirm('Delete squad?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-300">&times;</button></form>
                </span>
                @empty
                <span class="text-gray-500 text-sm">No squads yet.</span>
                @endforelse
            </div>
            <form method="POST" action="{{ route('clan.manage.squad.store', $clan->tracker_clan_id) }}" class="flex gap-2">
                @csrf
                <input name="name" placeholder="New squad name..." required class="flex-1 bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                <button class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">+ Add</button>
            </form>
        </div>
        @endif

        @if($isAdmin)
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5"
             x-data="{ q: '', results: [], picked: null, loading: false, async search() { if (this.q.length < 2) { this.results = []; this.picked = null; return; } this.loading = true; try { const r = await fetch('{{ route('clan.manage.member.search', $clan->tracker_clan_id) }}?q=' + encodeURIComponent(this.q)); this.results = await r.json(); } finally { this.loading = false; } }, pick(p) { this.picked = p; this.q = p.name; this.results = []; } }">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Add Member</h3>
            <form method="POST" action="{{ route('clan.manage.member.add', $clan->tracker_clan_id) }}" @submit="if(!picked){ $event.preventDefault(); alert('Please pick a player from the suggestions.'); }">
                @csrf
                <input type="hidden" name="player_id" :value="picked?.id ?? ''">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start">
                    <div class="md:col-span-6 relative">
                        <input x-model="q" @input.debounce.250ms="search()" type="text" autocomplete="off" placeholder="Search player name (min 2 chars)..."
                               class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                        <div x-show="results.length > 0" x-cloak class="absolute z-20 left-0 mt-1 bg-gray-900 border border-gray-700 rounded-lg shadow-xl max-h-96 w-[420px] overflow-y-auto">
                            <template x-for="p in results" :key="p.id">
                                <button type="button" @click="pick(p)" class="w-full px-3 py-2 text-left hover:bg-gray-800 flex items-center gap-2 text-sm">
                                    <template x-if="p.country"><img :src="`https://flagcdn.com/${p.country.toLowerCase()}.svg`" class="w-4 h-3" :alt="p.country"></template>
                                    <span class="text-amber-400 flex-1" x-html="p.name_html || p.name"></span>
                                    <span class="text-gray-500 text-xs">ELO <span x-text="p.elo"></span></span>
                                </button>
                            </template>
                        </div>
                        <div x-show="loading" x-cloak class="text-xs text-gray-500 mt-1">Searching...</div>
                        <div x-show="picked" x-cloak class="text-xs text-green-400 mt-1">Selected: <span x-text="picked?.name"></span> <button type="button" @click="picked=null; q=''" class="text-gray-500 hover:text-gray-300 ml-1">(clear)</button></div>
                    </div>
                    <div class="md:col-span-3">
                        <select name="role_label" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-2 py-2 rounded-lg text-sm">
                            <option value="">— Role —</option>
                            @foreach($roleLabels as $rl)<option value="{{ $rl }}">{{ $rl }}</option>@endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <select name="squad_id" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-2 py-2 rounded-lg text-sm">
                            <option value="">— Squad —</option>
                            @foreach($squads as $squad)<option value="{{ $squad->id }}">{{ $squad->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <button type="submit" class="w-full px-3 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">+</button>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Pick a player from the live search. Already-assigned players are excluded automatically.</p>
            </form>
        </div>
        @endif

        <div class="bg-gray-800 rounded-lg border border-gray-700/50 overflow-hidden">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h3 class="text-white font-semibold">Member Roster — assign roles & squads</h3></div>
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/30">
                    <tr><th class="px-4 py-2">Player</th><th class="px-4 py-2">Role Label</th><th class="px-4 py-2">Squad</th><th class="px-4 py-2 w-20">Order</th><th class="px-4 py-2"></th><th class="px-4 py-2 w-10"></th></tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($members as $m)
                    <tr class="hover:bg-gray-700/30">
                        <td class="px-4 py-2 font-mono text-amber-400">{!! $m->player->name_html ?? e($m->player->name_clean ?? 'Unknown') !!}</td>
                        <form method="POST" action="{{ route('clan.manage.member', [$clan->tracker_clan_id, $m->id]) }}">
                            @csrf @method('PUT')
                            <td class="px-4 py-2">
                                <select name="role_label" class="bg-gray-900 border border-gray-600 text-gray-200 px-2 py-1 rounded text-xs">
                                    <option value="">—</option>
                                    @foreach($roleLabels as $rl)<option value="{{ $rl }}" {{ $m->role_label === $rl ? 'selected' : '' }}>{{ $rl }}</option>@endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <select name="squad_id" class="bg-gray-900 border border-gray-600 text-gray-200 px-2 py-1 rounded text-xs">
                                    <option value="">—</option>
                                    @foreach($squads as $squad)<option value="{{ $squad->id }}" {{ $m->squad_id == $squad->id ? 'selected' : '' }}>{{ $squad->name }}</option>@endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2"><input name="sort_order" type="number" value="{{ $m->sort_order }}" class="w-16 bg-gray-900 border border-gray-600 text-gray-200 px-2 py-1 rounded text-xs"></td>
                            <td class="px-4 py-2"><button class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-amber-400 rounded text-xs">Save</button></td>
                        </form>
                        <td class="px-4 py-2">
                            @if(in_array($manager->role, ['leader', 'owner']))
                            <div class="flex gap-1 justify-end">
                                <form method="POST" action="{{ route('clan.manage.member.remove', [$clan->tracker_clan_id, $m->id]) }}" onsubmit="return confirm('Remove {{ $m->player->name_clean }} from clan? (Can be re-added by Auto-Detection.)')">
                                    @csrf @method('DELETE')
                                    <button class="px-2 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded text-xs" title="Remove (can come back via Auto-Detect)">Remove</button>
                                </form>
                                <form method="POST" action="{{ route('clan.manage.member.block', [$clan->tracker_clan_id, $m->id]) }}" onsubmit="return confirm('Block {{ $m->player->name_clean }} permanently? They will be removed AND prevented from being auto-pooled again.')">
                                    @csrf
                                    <input type="hidden" name="block_type" value="both">
                                    <button class="px-2 py-1 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded text-xs" title="Block (permanent)">🚫 Block</button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- BLOCKED PLAYERS --}}
        @if(in_array($manager->role, ['leader', 'owner']))
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 space-y-4 mt-6">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <div>
                    <h3 class="text-white font-semibold text-sm uppercase tracking-wide">🚫 Blocked players ({{ $blocks->count() }})</h3>
                    <p class="text-xs text-gray-500 mt-1">Players in this list are excluded from auto-detection. They won't reappear in the member list.</p>
                </div>
            </div>

            @if($blocks->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-gray-500 uppercase border-b border-gray-700">
                        <th class="px-3 py-2">Target</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Reason</th>
                        <th class="px-3 py-2">Blocked by</th>
                        <th class="px-3 py-2">When</th>
                        <th class="px-3 py-2 text-right">Action</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-700/50">
                        @foreach($blocks as $b)
                        <tr>
                            <td class="px-3 py-2 text-gray-200 font-mono text-xs">
                                @if($b->block_type === 'player_id' && $b->targetPlayer)
                                    {{ $b->targetPlayer->name_clean }} <span class="text-gray-500">#{{ $b->target_player_id }}</span>
                                @elseif($b->block_type === 'name')
                                    {{ $b->target_name }}
                                @else
                                    <span class="text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2"><span class="px-2 py-0.5 rounded text-xs uppercase {{ $b->block_type === 'player_id' ? 'bg-purple-900/30 text-purple-400' : 'bg-blue-900/30 text-blue-400' }}">{{ $b->block_type === 'player_id' ? 'Player' : 'Name' }}</span></td>
                            <td class="px-3 py-2 text-gray-400 text-xs">{{ $b->reason ?: '—' }}</td>
                            <td class="px-3 py-2 text-gray-400 text-xs">{{ $b->blockedBy?->name ?? 'Unknown' }}</td>
                            <td class="px-3 py-2 text-gray-500 text-xs">{{ $b->created_at?->diffForHumans() }}</td>
                            <td class="px-3 py-2 text-right">
                                <form method="POST" action="{{ route('clan.manage.block.remove', [$clan->tracker_clan_id, $b->id]) }}" onsubmit="return confirm('Unblock this entry? They can be auto-pooled again.')">
                                    @csrf @method('DELETE')
                                    <button class="px-2 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded text-xs">Unblock</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-gray-500 text-sm">No blocked players.</p>
            @endif

            {{-- Manual add block --}}
            <div class="border-t border-gray-700 pt-4">
                <h4 class="text-white font-semibold text-xs uppercase tracking-wide mb-2">Add block manually (by name)</h4>
                <form method="POST" action="{{ route('clan.manage.block.add', $clan->tracker_clan_id) }}" class="grid grid-cols-1 md:grid-cols-12 gap-2">
                    @csrf
                    <input type="hidden" name="block_type" value="name">
                    <input name="target_name" required maxlength="255" placeholder="Player name (exact match)" class="md:col-span-5 bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded text-sm focus:outline-none focus:border-amber-500">
                    <input name="reason" maxlength="500" placeholder="Reason (optional)" class="md:col-span-5 bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded text-sm focus:outline-none focus:border-amber-500">
                    <button class="md:col-span-2 px-3 py-2 bg-red-900/30 hover:bg-red-900/50 text-red-400 rounded text-sm">🚫 Block</button>
                </form>
                <p class="text-xs text-gray-500 mt-2">Use name-block for fake/imposter handles. The name must match exactly (case-insensitive).</p>
            </div>
        </div>
        @endif
    </div>

    {{-- ========================= SERVERS ========================= --}}
    <div x-show="tab==='servers'" x-cloak class="space-y-5">
        <div class="bg-amber-900/10 border border-amber-500/20 rounded-lg p-4 text-sm text-gray-300">
            <div class="font-semibold text-amber-400 mb-1">How clan servers work</div>
            Auto-detected matches are based on your clan tag display: <span class="font-mono text-amber-400">{{ $clan->display_tag }}</span>.
            Any server whose hostname starts with this prefix appears below. Toggle <span class="text-amber-400">Show on public page</span> for the ones you want to adopt.
            Server owners can also <span class="text-amber-400">claim</span> their server via the tracker for a stronger link.
        </div>

        @if($claimedServers->isEmpty() && $autoDetectedServers->isEmpty())
            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 text-center text-gray-500 text-sm">
                No servers match this clan. Either claim a server in the tracker, or set a clearer tag display in the Content tab.
            </div>
        @else
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/30 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-2">Server</th>
                        <th class="px-4 py-2">Source</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2 w-40 text-right">Public Page</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($claimedServers as $s)
                    <tr class="hover:bg-gray-700/30">
                        <td class="px-4 py-2">
                            <a href="{{ route('tracker.server.show', $s) }}" class="font-mono text-amber-400 hover:text-amber-300">{!! $s->hostname_html ?? e($s->hostname_clean) !!}</a>
                            <div class="text-xs text-gray-500 font-mono">{{ $s->ip }}:{{ $s->port }}</div>
                        </td>
                        <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-[10px] uppercase tracking-wide bg-green-900/30 text-green-400 border border-green-500/30">&#128737; Claimed</span></td>
                        <td class="px-4 py-2">
                            @if($s->is_online)<span class="text-green-400 text-xs">&#9679; Online</span>@else<span class="text-gray-500 text-xs">&#9675; Offline</span>@endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('clan.manage.server.toggle', [$clan->tracker_clan_id, $s->id]) }}" class="inline">
                                @csrf
                                <input type="hidden" name="visible" value="{{ $s->is_visible_for_clan ? 0 : 1 }}">
                                <button class="px-3 py-1 rounded text-xs {{ $s->is_visible_for_clan ? 'bg-amber-500 text-gray-900 hover:bg-amber-400' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                                    {{ $s->is_visible_for_clan ? 'Visible' : 'Hidden' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @foreach($autoDetectedServers as $s)
                    <tr class="hover:bg-gray-700/30">
                        <td class="px-4 py-2">
                            <a href="{{ route('tracker.server.show', $s) }}" class="font-mono text-amber-400 hover:text-amber-300">{!! $s->hostname_html ?? e($s->hostname_clean) !!}</a>
                            <div class="text-xs text-gray-500 font-mono">{{ $s->ip }}:{{ $s->port }}</div>
                        </td>
                        <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-[10px] uppercase tracking-wide bg-blue-900/30 text-blue-400 border border-blue-500/30">&#129302; Auto-detected</span></td>
                        <td class="px-4 py-2">
                            @if($s->is_online)<span class="text-green-400 text-xs">&#9679; Online</span>@else<span class="text-gray-500 text-xs">&#9675; Offline</span>@endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('clan.manage.server.toggle', [$clan->tracker_clan_id, $s->id]) }}" class="inline">
                                @csrf
                                <input type="hidden" name="visible" value="{{ $s->is_visible_for_clan ? 0 : 1 }}">
                                <button class="px-3 py-1 rounded text-xs {{ $s->is_visible_for_clan ? 'bg-amber-500 text-gray-900 hover:bg-amber-400' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                                    {{ $s->is_visible_for_clan ? 'Adopted' : 'Adopt' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ========================= NEWS ========================= --}}
    <div x-show="tab==='news'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-3">
            @forelse($news as $post)
            <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-4 flex items-start justify-between gap-3">
                <div>
                    <h4 class="text-white font-semibold">{{ $post->title }} @unless($post->is_published)<span class="text-xs text-amber-400">(draft)</span>@endunless</h4>
                    <div class="text-xs text-gray-500 font-mono">{{ $post->created_at->diffForHumans() }}</div>
                </div>
                <form method="POST" action="{{ route('clan.manage.news.delete', [$clan->tracker_clan_id, $post->id]) }}" onsubmit="return confirm('Delete this news post?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-300 text-sm">Delete</button></form>
            </div>
            @empty
            <p class="text-gray-500 text-sm">No news yet.</p>
            @endforelse
        </div>
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 self-start">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Post News</h3>
            <form method="POST" action="{{ route('clan.manage.news.store', $clan->tracker_clan_id) }}" class="space-y-3">
                @csrf
                <input name="title" placeholder="Title" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                <input name="excerpt" placeholder="Short excerpt (optional)" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                <textarea name="content" rows="5" placeholder="Content (Markdown + BBCode)" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm font-mono focus:outline-none focus:border-amber-500"></textarea>
                <button class="w-full px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">Publish</button>
            </form>
        </div>
    </div>

    {{-- ========================= MANAGERS ========================= --}}
    @if($isAdmin)
    <div x-show="tab==='managers'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-gray-800 rounded-lg border border-gray-700/50 overflow-hidden">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h3 class="text-white font-semibold">Clan Managers</h3></div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($clan->managers as $mgr)
                    <tr class="hover:bg-gray-700/30">
                        <td class="px-4 py-3 text-gray-200">{{ $mgr->user->name ?? 'Unknown' }} @if($mgr->user_id === auth()->id())<span class="text-gray-500 text-xs">(you)</span>@endif</td>
                        <td class="px-4 py-3">
                            @if($mgr->role === 'leader')
                                <span class="px-2 py-0.5 rounded-full text-xs uppercase tracking-wide border text-amber-400 border-amber-500/40 bg-amber-900/10">owner</span>
                            @elseif($isOwner)
                                <form method="POST" action="{{ route('clan.manage.manager.update', [$clan->tracker_clan_id, $mgr->id]) }}" class="inline">
                                    @csrf @method('PUT')
                                    <select name="role" onchange="this.form.submit()" class="bg-gray-900 border border-gray-600 text-gray-200 px-2 py-1 rounded text-xs">
                                        <option value="admin" {{ $mgr->role==='owner'?'selected':'' }}>admin</option>
                                        <option value="editor" {{ $mgr->role==='editor'?'selected':'' }}>editor</option>
                                    </select>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs uppercase">{{ $mgr->role }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex gap-2 justify-end items-center">
                            @if($isOwner && $mgr->role !== 'leader' && $mgr->user_id !== auth()->id())
                                <form method="POST" action="{{ route('clan.manage.manager.transfer', [$clan->tracker_clan_id, $mgr->id]) }}" onsubmit="return confirm('Transfer ownership to {{ $mgr->user->name ?? 'this user' }}? You will become an editor. Only the new owner can transfer back.')">
                                    @csrf
                                    <button class="text-amber-400 hover:text-amber-300 text-xs" title="Transfer ownership">Transfer</button>
                                </form>
                            @endif
                            @if($mgr->role !== 'leader')
                                <form method="POST" action="{{ route('clan.manage.manager.delete', [$clan->tracker_clan_id, $mgr->id]) }}" onsubmit="return confirm('Remove this manager?')">@csrf @method('DELETE')<button class="text-red-400 hover:text-red-300 text-xs">Remove</button></form>
                            @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 self-start">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-3">Add Manager</h3>
            <form method="POST" action="{{ route('clan.manage.manager.store', $clan->tracker_clan_id) }}" class="space-y-3">
                @csrf
                <input name="identifier" placeholder="Username or email" required class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                <select name="role" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm">
                    <option value="editor">editor (content + news)</option>
                    <option value="admin">admin (everything but delete)</option>
                </select>
                <button class="w-full px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm">+ Invite</button>
            </form>
            <p class="text-xs text-gray-500 mt-3"><b class="text-gray-400">owner</b>: all + delete/transfer · <b class="text-gray-400">admin</b>: manage members/managers/apps · <b class="text-gray-400">editor</b>: content & news only</p>
        </div>
    </div>

    {{-- ========================= APPLICATIONS ========================= --}}
    <div x-show="tab==='apps'" x-cloak class="space-y-3">
        @forelse($applications as $app)
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-4 flex items-start justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h4 class="text-white font-semibold">{{ $app->player_name }}</h4>
                    @php $sc = ['pending'=>'amber','accepted'=>'green','rejected'=>'red','withdrawn'=>'gray'][$app->status] ?? 'gray'; @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs uppercase tracking-wide border text-{{ $sc }}-400 border-{{ $sc }}-500/40 bg-{{ $sc }}-900/10">{{ $app->status }}</span>
                </div>
                @if($app->contact)<div class="text-xs text-gray-500 font-mono mt-0.5">{{ $app->contact }}</div>@endif
                <p class="text-sm text-gray-300 mt-2">{{ $app->message }}</p>
                <div class="text-xs text-gray-500 font-mono mt-1">{{ $app->created_at->diffForHumans() }}@if($app->applicant) · user: {{ $app->applicant->name }}@endif</div>
            </div>
            @if($app->status === 'pending')
            <div class="flex flex-col gap-2 shrink-0">
                <form method="POST" action="{{ route('clan.manage.app.review', [$clan->tracker_clan_id, $app->id]) }}">@csrf @method('PUT')<input type="hidden" name="decision" value="accepted"><button class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded text-xs w-full">Accept</button></form>
                <form method="POST" action="{{ route('clan.manage.app.review', [$clan->tracker_clan_id, $app->id]) }}">@csrf @method('PUT')<input type="hidden" name="decision" value="rejected"><button class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded text-xs w-full">Reject</button></form>
            </div>
            @endif
        </div>
        @empty
        <p class="text-gray-500 text-sm">No applications yet.</p>
        @endforelse
    </div>
    @endif

    {{-- ========================= API KEYS (owner only) ========================= --}}
    @if($manager->role === 'leader')
    <div x-show="tab==='api'" x-cloak class="space-y-4">
        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5">
            <h3 class="text-white font-semibold text-sm uppercase tracking-wide mb-2">ClanNews Tool</h3>
            <p class="text-sm text-gray-400 mb-3">Use this API key with the <a href="https://github.com/wolffileseu/clan-news-tool/releases/latest" target="_blank" rel="noopener" class="text-amber-400 hover:text-amber-300 underline">ClanNews Tool</a> to post news, events, matches, and recruitment posts directly from your desktop.</p>
            <div class="text-xs text-gray-500 font-mono">Endpoints: /api/v1/clan/{me,news,event,match,recruitment}</div>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700/50 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-900/50 text-gray-400 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Label</th>
                        <th class="px-4 py-2.5 text-left">Key</th>
                        <th class="px-4 py-2.5 text-left">Status</th>
                        <th class="px-4 py-2.5 text-left">Last used</th>
                        <th class="px-4 py-2.5 text-left">Expires</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @forelse($apiKeys as $k)
                        @php
                            $rawKey = $k->getAttributes()['key'] ?? '';
                            $isPending = str_starts_with($rawKey, 'PENDING:');
                            $isExpired = $k->expires_at && $k->expires_at->isPast();
                        @endphp
                        <tr class="text-gray-200">
                            <td class="px-4 py-3">{{ $k->label ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">
                                @if($isPending)
                                    <span class="text-amber-400">⏳ Awaiting admin review</span>
                                @else
                                    <span class="select-all break-all">{{ $rawKey }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($isPending)
                                    <span class="px-2 py-0.5 rounded-full text-xs border text-amber-400 border-amber-500/40 bg-amber-900/10">Pending</span>
                                @elseif($isExpired)
                                    <span class="px-2 py-0.5 rounded-full text-xs border text-gray-400 border-gray-500/40 bg-gray-900/10">Expired</span>
                                @elseif($k->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-xs border text-green-400 border-green-500/40 bg-green-900/10">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs border text-red-400 border-red-500/40 bg-red-900/10">Disabled</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $k->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $k->expires_at?->format('Y-m-d') ?? 'Never' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500 text-sm">No API keys yet. Request one below.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700/50 p-5 flex items-center justify-between gap-4">
            <div>
                <h4 class="text-white font-semibold text-sm">Request a new API key</h4>
                <p class="text-xs text-gray-400 mt-1">@if($hasPendingApiKey)You already have a pending request. An admin will review it.@else An admin will review and issue your key.@endif</p>
            </div>
            <form method="POST" action="{{ route('clan.manage.api-key.request', $clan->tracker_clan_id) }}">
                @csrf
                <button type="submit" @if($hasPendingApiKey) disabled @endif class="px-4 py-2 rounded-lg text-sm font-semibold transition @if($hasPendingApiKey) bg-gray-700 text-gray-500 cursor-not-allowed @else bg-amber-500 hover:bg-amber-400 text-gray-900 @endif">Request key</button>
            </form>
        </div>
    </div>
    @endif

</div>
</x-layouts.app>
