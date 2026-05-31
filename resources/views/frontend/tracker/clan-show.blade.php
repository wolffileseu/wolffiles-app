<x-layouts.app :title="'[' . $clan->tag_clean . '] ' . ($clan->name ?? $clan->tag_clean)">
@php
    $tagDisplay = $registered?->tag_display ?: ("[" . $clan->tag_clean . "]");
    $clanName   = $registered->name ?? $clan->name ?? 'Unknown Clan';
    $isClaimed  = (bool) $registered;
    $canManage  = $managerRole && in_array($managerRole, [\App\Models\ClanManager::ROLE_OWNER, \App\Models\ClanManager::ROLE_ADMIN, \App\Models\ClanManager::ROLE_EDITOR]);
    // logo/banner from registered clan if present
    $logoUrl   = $registered?->logo ? (\Illuminate\Support\Str::startsWith($registered->logo, ['http://','https://']) ? $registered->logo : asset('storage/'.$registered->logo)) : null;
    $bannerUrl = $registered?->banner ? (\Illuminate\Support\Str::startsWith($registered->banner, ['http://','https://']) ? $registered->banner : asset('storage/'.$registered->banner)) : null;
@endphp

<div class="max-w-7xl mx-auto px-4 py-8" x-data="{ tab: 'home' }">

    <a href="{{ route('tracker.clans') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back to Clans</a>

    {{-- ===================== HERO ===================== --}}
    <div class="bg-gray-800 rounded-lg overflow-hidden mt-4 mb-6 border border-gray-700/50">
        {{-- banner --}}
        <div class="h-28 md:h-36 relative bg-gradient-to-br from-amber-900/30 via-gray-800 to-gray-900">
            @if($bannerUrl)
                <img src="{{ $bannerUrl }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-60">
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-gray-800 to-transparent"></div>
        </div>

        <div class="px-6 pb-6 -mt-12 relative flex flex-wrap items-end gap-4">
            {{-- logo --}}
            <div class="w-20 h-20 md:w-24 md:h-24 bg-gray-700 rounded-xl flex items-center justify-center text-amber-400 font-bold text-2xl border-2 border-amber-500 shadow-lg overflow-hidden shrink-0">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $tagDisplay }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($tagDisplay, 0, 4)) }}
                @endif
            </div>

            <div class="flex-1 min-w-[220px] pb-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl md:text-3xl font-bold text-white">
                        <span class="text-amber-500">{{ $tagDisplay }}</span> {{ $clanName }}
                    </h1>
                    @if($isClaimed && ($clan->is_verified || $registered->is_active))
                        <span class="px-2.5 py-1 bg-green-900/30 text-green-400 rounded-md text-xs border border-green-500/30">&#x2713; Verified Clan</span>
                    @endif
                    @if($isClaimed && $registered->is_recruiting)
                        <span class="px-2.5 py-1 bg-green-900/30 text-green-400 rounded-md text-xs border border-green-500/30">&#9679; Recruiting</span>
                    @endif
                </div>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-400 flex-wrap">
                    @if($registered?->location)<span>{{ $registered->location }}</span>@endif
                    @if($registered?->founded)<span>&middot; founded {{ $registered->founded }}</span>
                    @elseif($clan->first_seen_at)<span>&middot; since {{ $clan->first_seen_at->format('M Y') }}</span>@endif
                </div>
            </div>

            {{-- actions --}}
            <div class="flex items-end gap-2 pb-1 flex-wrap">
                @auth
                    @if($isClaimed && $registered->is_recruiting && !$canManage)
                        <button @click="tab='apply'" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm transition">&#9166; Apply</button>
                    @endif
                    @if($canManage)
                        <a href="{{ route('clan.manage', $registered->slug) }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm transition">&#9881; Manage</a>
                    @elseif(!$isClaimed && !$clan->claimed_by_user_id)
                        <a href="{{ route('tracker.claim.clan', $clan) }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-amber-400 rounded-lg text-sm border border-amber-500/30 transition">&#x1f3f0; Claim Clan</a>
                    @endif
                @else
                    @if(!$isClaimed && !$clan->claimed_by_user_id)
                        <a href="{{ route('login') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-amber-400 rounded-lg text-sm border border-amber-500/30 transition">&#x1f3f0; Claim Clan</a>
                    @endif
                @endauth
            </div>
        </div>

        {{-- stat strip --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-700/50 border-t border-gray-700/50">
            <div class="bg-gray-800 py-4 text-center"><div class="text-2xl font-bold text-amber-400">{{ $clan->member_count }}</div><div class="text-gray-500 text-xs uppercase tracking-wide">Members</div></div>
            <div class="bg-gray-800 py-4 text-center"><div class="text-2xl font-bold text-green-400">{{ $clan->active_member_count ?? 0 }}</div><div class="text-gray-500 text-xs uppercase tracking-wide">Active</div></div>
            <div class="bg-gray-800 py-4 text-center"><div class="text-2xl font-bold text-white">{{ number_format($clan->avg_elo) }}</div><div class="text-gray-500 text-xs uppercase tracking-wide">Avg ELO</div></div>
            <div class="bg-gray-800 py-4 text-center"><div class="text-2xl font-bold text-blue-400">{{ round(($clan->total_play_time_minutes ?? 0) / 60) }}h</div><div class="text-gray-500 text-xs uppercase tracking-wide">Playtime</div></div>
        </div>
    </div>

    {{-- flash --}}
    @if(session('success'))<div class="mb-4 px-4 py-3 bg-green-900/30 border border-green-500/30 text-green-300 rounded-lg text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 px-4 py-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg text-sm">{{ session('error') }}</div>@endif

    {{-- ===================== TABS ===================== --}}
    @if($isClaimed)
    <div class="flex gap-1 border-b border-gray-700 mb-6 flex-wrap">
        <button @click="tab='home'"    :class="tab==='home'    ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Home</button>
        <button @click="tab='news'"    :class="tab==='news'    ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">News<span class="text-gray-600 ml-1">{{ $news->count() }}</span></button>
        <button @click="tab='members'" :class="tab==='members' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Members<span class="text-gray-600 ml-1">{{ $clan->member_count }}</span></button>
        <button @click="tab='servers'" :class="tab==='servers' ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Servers<span class="text-gray-600 ml-1">{{ $clanServers->count() }}</span></button>
        @if($registered->is_recruiting)
        <button @click="tab='apply'"   :class="tab==='apply'   ? 'text-amber-400 border-amber-500' : 'text-gray-400 border-transparent hover:text-gray-200'" class="px-4 py-2.5 text-sm font-medium uppercase tracking-wide border-b-2 transition">Apply</button>
        @endif
    </div>
    @endif

    {{-- =============================================================
         HOME
    ============================================================= --}}
    <div x-show="tab==='home'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if($isClaimed && $registered->description)
            <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
                <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">About</h2></div>
                <div class="p-4 prose prose-invert prose-sm max-w-none text-gray-300">{!! \App\Helpers\BBCode::parse($registered->description) !!}</div>
            </div>
            @endif

            @if($isClaimed && $registered->rules)
            <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
                <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">&#128220; Clan Rules</h2></div>
                <div class="p-4 prose prose-invert prose-sm max-w-none text-gray-300">{!! \App\Helpers\BBCode::parse($registered->rules) !!}</div>
            </div>
            @endif

            {{-- Auto-clan fallback: lean roster preview when not claimed --}}
            @unless($isClaimed)
            <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
                <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700 flex items-center justify-between">
                    <h2 class="text-white font-semibold">Members</h2>
                    <span class="text-xs text-gray-500">auto-detected from player tags</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-gray-400 text-left bg-gray-900/30">
                        <tr><th class="px-4 py-2">#</th><th class="px-4 py-2">Player</th><th class="px-4 py-2 text-center">ELO</th><th class="px-4 py-2">Last Seen</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        @foreach($topPlayers as $i => $player)
                        <tr class="hover:bg-gray-700/30 transition">
                            <td class="px-4 py-2 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-2"><a href="{{ route('tracker.player.show', $player) }}" class="text-amber-400 hover:text-amber-300">{!! $player->name_html ?? e($player->name_clean ?? 'Unknown') !!}</a></td>
                            <td class="px-4 py-2 text-center text-white font-medium">{{ number_format($player->elo_rating) }}</td>
                            <td class="px-4 py-2 text-gray-400 text-xs">{{ $player->last_seen_at?->diffForHumans() ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="bg-amber-900/10 border border-amber-500/20 rounded-lg p-4 text-sm text-gray-400">
                This clan was automatically detected from player name tags. If you represent
                <span class="text-amber-400">{{ $tagDisplay }}</span>, claim it to unlock a full editable page with news, rules, servers and recruitment.
            </div>
            @endunless
        </div>

        {{-- sidebar --}}
        <div class="space-y-6">
            <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
                <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">Clan Info</h2></div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-700/50">
                        <tr><td class="px-4 py-2.5 text-gray-500 uppercase text-xs">Clantag</td><td class="px-4 py-2.5"><span class="text-amber-400">{{ $tagDisplay }}</span></td></tr>
                        @if($registered?->location)<tr><td class="px-4 py-2.5 text-gray-500 uppercase text-xs">Location</td><td class="px-4 py-2.5 text-gray-300">{{ $registered->location }}</td></tr>@endif
                        <tr><td class="px-4 py-2.5 text-gray-500 uppercase text-xs">Members</td><td class="px-4 py-2.5 text-gray-300">{{ $clan->member_count }}</td></tr>
                        @if($isClaimed)<tr><td class="px-4 py-2.5 text-gray-500 uppercase text-xs">Recruiting</td><td class="px-4 py-2.5">@if($registered->is_recruiting)<span class="text-green-400">YES</span>@else<span class="text-gray-500">NO</span>@endif</td></tr>@endif
                        @if($registered?->ts_address)<tr><td class="px-4 py-2.5 text-gray-500 uppercase text-xs">TS / Vent</td><td class="px-4 py-2.5 text-gray-300 font-mono text-xs">{{ $registered->ts_address }}</td></tr>@endif
                        @if($registered?->founded)<tr><td class="px-4 py-2.5 text-gray-500 uppercase text-xs">Founded</td><td class="px-4 py-2.5 text-gray-300">{{ $registered->founded }}</td></tr>@endif
                    </tbody>
                </table>
            </div>

            @if($isClaimed && ($registered->website || $registered->contact_discord))
            <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
                <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">Links</h2></div>
                <div class="p-4 space-y-2">
                    @if($registered->website)<a href="{{ $registered->website }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-3 py-2.5 bg-gray-900/40 border border-gray-700 rounded-lg text-sm hover:border-amber-500/30 transition"><span>&#127760;</span> <span class="text-gray-300">Website</span> <span class="ml-auto text-amber-400">&#8599;</span></a>@endif
                    @if($registered->contact_discord)<a href="{{ \Illuminate\Support\Str::startsWith($registered->contact_discord,'http') ? $registered->contact_discord : 'https://'.$registered->contact_discord }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-3 py-2.5 bg-gray-900/40 border border-gray-700 rounded-lg text-sm hover:border-amber-500/30 transition"><span>&#128172;</span> <span class="text-gray-300">Discord</span> <span class="ml-auto text-amber-400">&#8599;</span></a>@endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- =============================================================
         NEWS
    ============================================================= --}}
    @if($isClaimed)
    <div x-show="tab==='news'" x-cloak>
        <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">Clan News</h2></div>
            <div class="p-4 space-y-5">
                @forelse($news as $post)
                <div class="border-l-2 border-amber-500/40 pl-4">
                    <h3 class="text-lg font-semibold text-white">{{ $post->title }}</h3>
                    <div class="text-xs text-gray-500 font-mono mb-2">{{ $post->published_at?->diffForHumans() }}</div>
                    <div class="prose prose-invert prose-sm max-w-none text-gray-300">{!! \App\Helpers\BBCode::parse($post->excerpt ?: \Illuminate\Support\Str::limit($post->content, 400)) !!}</div>
                </div>
                @empty
                <p class="text-gray-500 text-sm">No news posted yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- =============================================================
         MEMBERS (grouped by squad)
    ============================================================= --}}
    <div x-show="tab==='members'" x-cloak>
        <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">Member Roster</h2></div>

            @foreach($membersBySquad as $squadName => $members)
            <div class="px-4 py-2 bg-gray-900/40 border-l-2 border-amber-500 text-amber-400 text-xs uppercase tracking-wide font-medium">&#9656; {{ $squadName }}</div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($members as $m)
                    @include('frontend.tracker.partials._clan-member-row', ['m' => $m])
                    @endforeach
                </tbody>
            </table>
            @endforeach

            @if($unassignedMembers->isNotEmpty())
            @if($membersBySquad->isNotEmpty())<div class="px-4 py-2 bg-gray-900/40 border-l-2 border-gray-600 text-gray-400 text-xs uppercase tracking-wide font-medium">&#9656; Members</div>@endif
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/30">
                    <tr><th class="px-4 py-2">Player</th><th class="px-4 py-2">Role</th><th class="px-4 py-2 text-center">ELO</th><th class="px-4 py-2">Last Seen</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($unassignedMembers as $m)
                    @include('frontend.tracker.partials._clan-member-row', ['m' => $m])
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- =============================================================
         SERVERS
    ============================================================= --}}
    <div x-show="tab==='servers'" x-cloak>
        <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">Clan Servers</h2></div>
            <div class="p-4 space-y-2">
                @forelse($clanServers as $server)
                <a href="{{ route('tracker.server.show', $server) }}" class="flex items-center gap-3 px-3 py-3 bg-gray-900/40 border border-gray-700 rounded-lg hover:border-amber-500/30 transition">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $server->is_online ? 'bg-green-500 shadow-[0_0_8px] shadow-green-500' : 'bg-red-500' }}"></span>
                    <span class="flex-1 min-w-0 font-mono text-sm text-gray-200 truncate">{!! $server->hostname_html ?? e($server->hostname_clean ?? $server->full_address) !!}</span>
                    <span class="text-xs text-gray-500 font-mono">{{ $server->current_map ?? '-' }}</span>
                    <span class="text-sm text-gray-300 font-mono">{{ $server->is_online ? $server->current_players.'/'.$server->max_players : 'offline' }}</span>
                </a>
                @empty
                <p class="text-gray-500 text-sm">No servers linked to this clan yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- =============================================================
         APPLY
    ============================================================= --}}
    @if($registered->is_recruiting)
    <div x-show="tab==='apply'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">Apply to {{ $clanName }}</h2></div>
            <div class="p-4">
                @auth
                    @if($userHasApplied)
                        <div class="px-4 py-3 bg-amber-900/20 border border-amber-500/30 text-amber-300 rounded-lg text-sm">You already have a pending application with this clan.</div>
                    @else
                        @if($recruitmentPost)
                        <div class="mb-4 px-4 py-3 bg-blue-900/20 border border-blue-500/30 rounded-lg text-sm text-gray-300">{!! \App\Helpers\BBCode::parse($recruitmentPost->excerpt ?: \Illuminate\Support\Str::limit($recruitmentPost->content, 300)) !!}</div>
                        @endif
                        <form method="POST" action="{{ route('clan.apply', $registered->slug) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">In-Game Name</label>
                                <input name="player_name" required value="{{ old('player_name') }}" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Contact (Discord)</label>
                                <input name="contact" value="{{ old('contact') }}" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">
                            </div>
                            <div>
                                <label class="block text-xs uppercase tracking-wide text-gray-400 mb-1.5">Why do you want to join?</label>
                                <textarea name="message" required rows="4" class="w-full bg-gray-900 border border-gray-600 text-gray-200 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-amber-500">{{ old('message') }}</textarea>
                            </div>
                            <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-gray-900 font-semibold rounded-lg text-sm transition">Submit Application</button>
                        </form>
                    @endif
                @else
                    <p class="text-gray-400 text-sm">Please <a href="{{ route('login') }}" class="text-amber-400 hover:text-amber-300">log in</a> to apply.</p>
                @endauth
            </div>
        </div>
        <div class="bg-gray-800 rounded-lg overflow-hidden border border-gray-700/50 self-start">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">Requirements</h2></div>
            <div class="p-4 text-sm text-gray-400">
                @if($registered->recruitment_summary)
                    {!! \App\Helpers\BBCode::parse($registered->recruitment_summary) !!}
                @else
                    <p>This clan is open for applications. Fill out the form to get in touch.</p>
                @endif
            </div>
        </div>
    </div>
    @endif
    @endif

</div>
</x-layouts.app>
