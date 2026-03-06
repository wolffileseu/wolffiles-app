<x-layouts.app :title="$user->name">
<style>
.profile-hero { background: linear-gradient(180deg,#1c1204 0%,transparent 100%); border-bottom:1px solid #374151; position:relative; overflow:hidden; }
.profile-hero::after { content:''; position:absolute; inset:0; background-image:linear-gradient(rgba(245,158,11,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(245,158,11,.03) 1px,transparent 1px); background-size:48px 48px; pointer-events:none; }
.hm-cell { aspect-ratio:1; border-radius:2px; cursor:default; }
.hm-0 { background:#2a2e37; } .hm-1 { background:rgba(245,158,11,.22); } .hm-2 { background:rgba(245,158,11,.48); } .hm-3 { background:rgba(245,158,11,.74); } .hm-4 { background:#f59e0b; }
.tab-btn { border-bottom:2px solid transparent; transition:all .15s; }
.tab-btn.active { color:#fbbf24; border-bottom-color:#f59e0b; }
.tab-btn:not(.active):hover { color:#f3f4f6; }
.fcard { transition:all .2s; }
.fcard:hover { border-color:#d97706 !important; transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.45); }
.fcard:hover .fc-thumb img { transform:scale(1.06); }
.chip { transition:all .15s; }
.chip.active { background:rgba(245,158,11,.1) !important; border-color:#f59e0b !important; color:#fbbf24 !important; }
.chip:not(.active):hover { border-color:#d97706; color:#fbbf24; }
.sb-accent::before { content:''; display:inline-block; width:3px; height:12px; background:#f59e0b; border-radius:2px; margin-right:8px; vertical-align:middle; }
</style>

<div class="profile-hero">
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-0">
        <div class="flex items-end gap-5 pb-5 flex-wrap">

            <div class="relative flex-shrink-0">
                <div class="w-24 h-24 rounded-full p-[3px]" style="background:linear-gradient(135deg,#f59e0b,#ea580c);box-shadow:0 0 28px rgba(245,158,11,.35);">
                    @if($user->avatar)
                        <img src="{{ \Storage::disk('s3')->url($user->avatar) }}"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=d97706&color=000&size=200&bold=true'"
                             class="w-full h-full rounded-full object-cover block bg-gray-900" alt="{{ $user->name }}">
                    @else
                        <div class="w-full h-full rounded-full bg-gray-900 flex items-center justify-center text-2xl font-bold text-amber-400" style="font-family:'Rajdhani',sans-serif;">
                            {{ strtoupper(substr($user->name,0,2)) }}
                        </div>
                    @endif
                </div>
                @if($user->last_activity && $user->last_activity->diffInMinutes() < 10)
                    <div class="absolute bottom-1 right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-gray-900" style="box-shadow:0 0 6px #22c55e;"></div>
                @endif
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-3xl font-bold text-white" style="font-family:'Rajdhani',sans-serif;letter-spacing:1.5px;">{{ $user->name }}</h1>
                    @foreach($user->getRoleNames() as $role)
                        <span class="text-xs px-2 py-0.5 rounded font-mono border" style="background:rgba(245,158,11,.12);border-color:#d97706;color:#fbbf24;letter-spacing:1px;">{{ strtoupper($role) }}</span>
                    @endforeach
                    @if($user->badges && $user->badges->isNotEmpty())
                        @foreach($user->badges as $badge)
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium" style="background-color:{{ $badge->color }}20;color:{{ $badge->color }};" title="{{ $badge->description }}">
                                @if($badge->icon){!! $badge->icon !!} @endif{{ $badge->name }}
                            </span>
                        @endforeach
                    @endif
                </div>
                <div class="flex items-center gap-4 mt-2 text-sm text-gray-400 flex-wrap">
                    <span>📅 {{ __('messages.member_since') ?? 'Mitglied seit' }} {{ $user->created_at->format('M Y') }}</span>
                    @if($user->website)
                        <a href="{{ $user->website }}" target="_blank" rel="noopener" class="text-amber-400 hover:text-amber-300">🌐 {{ parse_url($user->website, PHP_URL_HOST) }}</a>
                    @endif
                </div>
            </div>

            <div class="flex-shrink-0 flex gap-2 self-center">
                @auth
                    @if(auth()->id() !== $user->id)
                        {{-- TODO: messages.create route not yet implemented --}}
                    @else
                        <a href="{{ route('profile.settings') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-black transition-all" style="background:linear-gradient(135deg,#f59e0b,#ea580c);font-family:'Rajdhani',sans-serif;">⚙ {{ __('messages.settings') ?? 'Einstellungen' }}</a>
                    @endif
                @endauth
            </div>
        </div>

        <div class="flex border-b border-gray-700 -mb-px">
            <button onclick="showTab('overview')" id="tab-overview" class="tab-btn active px-5 py-3 text-sm font-medium text-amber-400">{{ __('messages.overview') ?? 'Übersicht' }}</button>
            <button onclick="showTab('uploads')" id="tab-uploads" class="tab-btn px-5 py-3 text-sm font-medium text-gray-400 flex items-center gap-1.5">
                {{ __('messages.uploads') ?? 'Uploads' }}
                <span class="text-xs px-2 py-0.5 rounded-full font-mono border border-gray-700 bg-gray-800 text-gray-400">{{ $uploads->total() }}</span>
            </button>
            @if($luaScripts->isNotEmpty())
            <button onclick="showTab('lua')" id="tab-lua" class="tab-btn px-5 py-3 text-sm font-medium text-gray-400 flex items-center gap-1.5">
                LUA <span class="text-xs px-2 py-0.5 rounded-full font-mono border border-gray-700 bg-gray-800 text-gray-400">{{ $luaScripts->count() }}</span>
            </button>
            @endif
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        <aside class="lg:col-span-1 flex flex-col gap-4">

            <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-700 text-xs font-semibold uppercase tracking-widest text-gray-400 flex items-center">
                    <span class="sb-accent">{{ __('messages.statistics') ?? 'Statistiken' }}</span>
                </div>
                <div class="p-3 grid grid-cols-2 gap-2">
                    <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 text-center hover:border-amber-700 transition-colors">
                        <div class="text-xl font-mono text-amber-400">{{ number_format($user->files()->where('status','approved')->count()) }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide mt-0.5">Uploads</div>
                    </div>
                    <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 text-center hover:border-amber-700 transition-colors">
                        <div class="text-xl font-mono text-amber-400">{{ number_format($user->files()->where('status','approved')->sum('download_count')) }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide mt-0.5">Downloads</div>
                    </div>
                    <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 text-center hover:border-amber-700 transition-colors">
                        <div class="text-xl font-mono text-amber-400">{{ $luaScripts->count() }}</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide mt-0.5">LUA</div>
                    </div>
                    <div class="bg-gray-900 border border-gray-700 rounded-lg p-3 text-center hover:border-amber-700 transition-colors">
                        <div class="text-xl font-mono text-amber-400">{{ (int)$user->created_at->diffInMonths() }}m</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide mt-0.5">Aktiv</div>
                    </div>
                </div>
            </div>

            @if($user->bio)
            <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-700 text-xs font-semibold uppercase tracking-widest text-gray-400 flex items-center">
                    <span class="sb-accent">Bio</span>
                </div>
                <div class="p-4 text-sm text-gray-400 leading-relaxed">{{ $user->bio }}</div>
            </div>
            @endif

            @if($user->website || $user->discord_username || $user->telegram_username || $user->clan)
            <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-700 text-xs font-semibold uppercase tracking-widest text-gray-400 flex items-center">
                    <span class="sb-accent">Links</span>
                </div>
                <div class="p-2">
                    @if($user->website)
                    <a href="{{ $user->website }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-amber-500/10 hover:text-amber-400 transition-all">
                        🌐 {{ parse_url($user->website, PHP_URL_HOST) }}
                    </a>
                    @endif
                    @if($user->discord_username)
                    <div class="flex items-center gap-2.5 px-3 py-2 text-sm">
                        <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                        <span class="text-indigo-400">{{ $user->discord_username }}</span>
                    </div>
                    @endif
                    @if($user->telegram_username)
                    <div class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-400">
                        ✈️ <span>{{ $user->telegram_username }}</span>
                    </div>
                    @endif
                    @if($user->clan)
                    <div class="flex items-center gap-2.5 px-3 py-2 text-sm text-gray-400">
                        🎮 <span>{{ $user->clan }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if($user->favorite_games && count($user->favorite_games) > 0)
            <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-700 text-xs font-semibold uppercase tracking-widest text-gray-400 flex items-center">
                    <span class="sb-accent">Spiele</span>
                </div>
                <div class="p-3 flex flex-wrap gap-2">
                    @foreach($user->favorite_games as $game)
                    @php $gameLabels = ['et'=>'Wolfenstein: ET','rtcw'=>'RtCW','etl'=>'ET: Legacy']; $gameColors = ['et'=>'rgba(245,158,11,.15)','rtcw'=>'rgba(239,68,68,.15)','etl'=>'rgba(34,197,94,.15)']; $gameBorders = ['et'=>'#d97706','rtcw'=>'#dc2626','etl'=>'#16a34a']; @endphp
                    <span class="text-xs px-2.5 py-1 rounded-full border font-semibold uppercase tracking-wide"
                          style="background:{{ $gameColors[$game] ?? 'rgba(107,114,128,.15)' }};border-color:{{ $gameBorders[$game] ?? '#6b7280' }};color:{{ $gameBorders[$game] ?? '#9ca3af' }}">
                        {{ $gameLabels[$game] ?? $game }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($user->badges && $user->badges->isNotEmpty())
            <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-700 text-xs font-semibold uppercase tracking-widest text-gray-400 flex items-center">
                    <span class="sb-accent">Achievements</span>
                </div>
                <div class="p-3 flex flex-wrap gap-2">
                    @foreach($user->badges as $badge)
                    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs border border-gray-700 bg-gray-900 text-gray-400 hover:border-amber-700 transition-colors cursor-default" title="{{ $badge->description }}">
                        @if($badge->icon){!! $badge->icon !!} @endif{{ $badge->name }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

        </aside>

        <main class="lg:col-span-3 min-w-0">

            <div id="pane-overview">
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-5">
                    <div class="flex justify-between text-xs text-gray-500 mb-2">
                        <span>Upload-Aktivität</span>
                        <span class="text-amber-400 font-mono">{{ number_format($user->files()->where('status','approved')->count()) }} Uploads</span>
                    </div>
                    <div class="grid gap-0.5" style="grid-template-columns:repeat(52,1fr);" id="heatmap"></div>
                    <div class="flex items-center gap-1 text-xs text-gray-600 justify-end mt-1.5">
                        Weniger
                        <div class="w-2.5 h-2.5 rounded-sm hm-0"></div>
                        <div class="w-2.5 h-2.5 rounded-sm hm-1"></div>
                        <div class="w-2.5 h-2.5 rounded-sm hm-2"></div>
                        <div class="w-2.5 h-2.5 rounded-sm hm-3"></div>
                        <div class="w-2.5 h-2.5 rounded-sm hm-4"></div>
                        Mehr
                    </div>
                </div>

                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white" style="font-family:'Rajdhani',sans-serif;letter-spacing:.8px;">⬆ {{ __('messages.recent_uploads') ?? 'Letzte Uploads' }}</h3>
                    <button onclick="showTab('uploads')" class="text-amber-400 text-xs hover:text-amber-300">{{ __('messages.view_all') ?? 'Alle anzeigen' }} →</button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($uploads->take(6) as $file)
                    <a href="{{ route('files.show', $file) }}" class="fcard block bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
                        <div class="h-24 overflow-hidden bg-gray-900 relative fc-thumb">
                            @if($file->screenshots->first())
                                <img src="{{ \Storage::disk('s3')->url($file->screenshots->first()->thumb_path ?? $file->screenshots->first()->path) }}"
                                     onerror="this.parentNode.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-size:28px;color:#374151\'>📄</div>'"
                                     class="w-full h-full object-cover transition-transform duration-300" alt="{{ $file->title }}">
                            @else
                                <div class="flex items-center justify-center h-full text-3xl text-gray-700">📄</div>
                            @endif
                            <span class="absolute top-1.5 right-1.5 text-xs font-mono font-bold px-1.5 py-0.5 rounded"
                                  style="{{ ($file->game ?? 'et') === 'rtcw' ? 'background:rgba(239,68,68,.85);color:#fff' : 'background:rgba(245,158,11,.9);color:#000' }}">
                                {{ strtoupper($file->game ?? 'ET') }}
                            </span>
                        </div>
                        <div class="p-2.5">
                            <div class="text-xs text-gray-600 uppercase tracking-wide mb-0.5">{{ $file->category->name ?? '' }}</div>
                            <div class="text-xs font-medium text-gray-200 truncate">{{ $file->title }}</div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1 font-mono">
                                <span>{{ $file->formatted_size ?? round($file->file_size/1048576,2).' MB' }}</span>
                                <span>⬇ {{ number_format($file->download_count) }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>

            <div id="pane-uploads" class="hidden">
                <div class="flex gap-2 flex-wrap items-center mb-4">
                    <button class="chip text-xs px-3 py-1.5 rounded-full border border-gray-700 bg-gray-800 text-gray-400 active" data-game="all" onclick="filterChip(this)">{{ __('messages.all') ?? 'Alle' }}</button>
                    <button class="chip text-xs px-3 py-1.5 rounded-full border border-gray-700 bg-gray-800 text-gray-400" data-game="et" onclick="filterChip(this)">ET</button>
                    <button class="chip text-xs px-3 py-1.5 rounded-full border border-gray-700 bg-gray-800 text-gray-400" data-game="rtcw" onclick="filterChip(this)">RtCW</button>
                    <input id="fileSearch" type="text" placeholder="🔍  Suchen…"
                           class="ml-auto bg-gray-800 border border-gray-700 rounded-lg px-3 py-1.5 text-xs text-gray-200 outline-none focus:border-amber-600 w-40 transition-colors"
                           oninput="filterFiles()">
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3" id="filesGrid">
                    @foreach($uploads as $file)
                    <a href="{{ route('files.show', $file) }}" class="fcard block bg-gray-800 border border-gray-700 rounded-lg overflow-hidden"
                       data-game="{{ $file->game ?? 'et' }}" data-title="{{ strtolower($file->title) }}">
                        <div class="h-24 overflow-hidden bg-gray-900 relative fc-thumb">
                            @if($file->screenshots->first())
                                <img src="{{ \Storage::disk('s3')->url($file->screenshots->first()->thumb_path ?? $file->screenshots->first()->path) }}"
                                     onerror="this.parentNode.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;font-size:28px;color:#374151\'>📄</div>'"
                                     class="w-full h-full object-cover transition-transform duration-300" alt="{{ $file->title }}">
                            @else
                                <div class="flex items-center justify-center h-full text-3xl text-gray-700">📄</div>
                            @endif
                            <span class="absolute top-1.5 right-1.5 text-xs font-mono font-bold px-1.5 py-0.5 rounded"
                                  style="{{ ($file->game ?? 'et') === 'rtcw' ? 'background:rgba(239,68,68,.85);color:#fff' : 'background:rgba(245,158,11,.9);color:#000' }}">
                                {{ strtoupper($file->game ?? 'ET') }}
                            </span>
                        </div>
                        <div class="p-2.5">
                            <div class="text-xs text-gray-600 uppercase tracking-wide mb-0.5">{{ $file->category->name ?? '' }}</div>
                            <div class="text-xs font-medium text-gray-200 truncate">{{ $file->title }}</div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1 font-mono">
                                <span>{{ $file->formatted_size ?? round($file->file_size/1048576,2).' MB' }}</span>
                                <span>⬇ {{ number_format($file->download_count) }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="mt-6">{{ $uploads->links() }}</div>
            </div>

            @if($luaScripts->isNotEmpty())
            <div id="pane-lua" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($luaScripts as $script)
                    <a href="{{ route('lua.show', $script) }}" class="block bg-gray-800 border border-gray-700 rounded-lg p-4 hover:border-amber-600 transition-colors">
                        <div class="font-medium text-white text-sm mb-1">{{ $script->title }}</div>
                        @if($script->description)
                            <div class="text-xs text-gray-400 mb-2">{{ Str::limit($script->description, 80) }}</div>
                        @endif
                        <div class="text-xs text-gray-500 font-mono">⬇ {{ number_format($script->download_count) }} Downloads</div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </main>
    </div>
</div>

<script>
function showTab(name) {
    ['overview','uploads','lua'].forEach(function(t) {
        var pane = document.getElementById('pane-'+t);
        var tab  = document.getElementById('tab-'+t);
        if (!pane || !tab) return;
        if (t === name) { pane.classList.remove('hidden'); tab.classList.add('active'); tab.style.color='#fbbf24'; }
        else            { pane.classList.add('hidden'); tab.classList.remove('active'); tab.style.color='#9ca3af'; }
    });
}
function filterChip(el) {
    document.querySelectorAll('.chip').forEach(function(c){ c.classList.remove('active'); });
    el.classList.add('active');
    filterFiles();
}
function filterFiles() {
    var search = document.getElementById('fileSearch') ? document.getElementById('fileSearch').value.toLowerCase() : '';
    var activeChip = document.querySelector('.chip.active');
    var game = activeChip ? activeChip.dataset.game : 'all';
    document.querySelectorAll('#filesGrid [data-game]').forEach(function(card) {
        var matchGame   = game === 'all' || card.dataset.game === game;
        var matchSearch = !search || card.dataset.title.indexOf(search) !== -1;
        card.style.display = (matchGame && matchSearch) ? '' : 'none';
    });
}
var hm = document.getElementById('heatmap');
if (hm) {
    var uploadDays = @json($uploadHeatmap ?? []);
    var today = new Date();
    today.setHours(0,0,0,0);
    var startDay = new Date(today);
    startDay.setDate(startDay.getDate() - (52*7 - 1));
    // align to Monday
    var dow = startDay.getDay();
    var diff = (dow === 0) ? -6 : 1 - dow;
    startDay.setDate(startDay.getDate() + diff);
    for (var i = 0; i < 52*7; i++) {
        var d = new Date(startDay);
        d.setDate(d.getDate() + i);
        var key = d.toISOString().slice(0,10);
        var count = uploadDays[key] || 0;
        var l = count === 0 ? 0 : count === 1 ? 1 : count <= 3 ? 2 : count <= 6 ? 3 : 4;
        var cell = document.createElement('div');
        cell.className = 'hm-cell hm-'+l;
        cell.title = key + ': ' + count + ' Upload(s)';
        hm.appendChild(cell);
    }
}
</script>
</x-layouts.app>
