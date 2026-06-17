{{--
    Tracker API — Extended endpoints (Phases 1–5).
    Included from frontend/api-docs.blade.php via @include, rendered inside the
    same x-data="{ openEndpoint: null }" scope, immediately before the Rate Limits section.

    Hardcoded English to match the existing tracker blocks (the tracker section
    is not localized; only the Files section uses __()).

    JSON examples are REAL responses captured from the live API during build.
    Per-endpoint code samples use a local x-data="{ lang: 'php' }" toggle,
    cloned from the page's shared Code Examples section.

    NOTE: /players/{id}/elo-history and /players/{id}/daily are intentionally
    NOT documented here — their source tables are currently empty (no populating
    job). The endpoints remain live; document them once data flows.
--}}

{{-- Section: Tracker — Players (extended) --}}
<h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2 pt-4">🎯 Tracker API — Player Combat</h2>

{{-- GET /tracker/players/{id}/stats --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-player-stats' ? null : 'tracker-player-stats'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/players/{id}/stats</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Aggregate combat totals (K/D, XP, enhanced, ELO)</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-player-stats' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-player-stats'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns a player's lifetime combat totals: basic kills/deaths/XP/playtime, the <code class="text-gray-400">enhanced</code> block (headshots, damage, match count — only meaningful when <code class="text-gray-400">available</code> is true), and current ELO. Complements <code class="text-gray-400">/tracker/players/{id}</code> (profile).</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Path Parameter</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Tracker player ID <span class="text-red-400 text-xs">(required)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/players/1/stats</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": {
    "player_id": 1,
    "totals": {
      "kills": 0, "deaths": 0, "kd_ratio": 0,
      "xp": 5498107, "play_time_minutes": 3924, "sessions": 339
    },
    "enhanced": {
      "available": false,
      "kills": 0, "deaths": 0, "headshots": 0,
      "damage": 0, "matches": 0, "kd_ratio": 0, "headshot_pct": 0
    },
    "elo": { "rating": 1797.49, "peak": 1797.49, "games": 0, "level": 0 }
  }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$data = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/players/1/stats'
), true)['data'];
echo "K/D: {$data['totals']['kd_ratio']} — XP: {$data['totals']['xp']}\n";</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

d = requests.get('https://wolffiles.eu/api/v1/tracker/players/1/stats').json()['data']
print(f"K/D: {d['totals']['kd_ratio']} — XP: {d['totals']['xp']}")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/players/1/stats', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/players/{id}/weapons --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-player-weapons' ? null : 'tracker-player-weapons'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/players/{id}/weapons</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Per-weapon stats (accuracy, headshots)</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-player-weapons' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-player-weapons'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns per-weapon stats for a player (hits, attempts, kills, deaths, headshots), with accuracy computed from hits/attempts. Each weapon includes resolved metadata (name, slug, category, side, icon) when known. Empty for players with no recorded weapon data (e.g. seen only on non-enhanced servers).</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Path Parameter</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Tracker player ID <span class="text-red-400 text-xs">(required)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/players/379/weapons</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "weapon_bit": 3,
      "weapon": {
        "name": "Colt .45", "slug": "colt", "category": "pistol",
        "side": "allied", "icon": "https://wolffiles.eu/img/tracker/weapons/iconw_colt.svg"
      },
      "hits": 40102, "attempts": 153450, "kills": 100715,
      "deaths": 85065, "headshots": 41827, "accuracy": 26.13
    }
  ],
  "meta": { "player_id": 379, "count": 18 }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$weapons = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/players/379/weapons'
), true)['data'];
foreach ($weapons as $w) {
    $name = $w['weapon']['name'] ?? "bit {$w['weapon_bit']}";
    echo "{$name}: {$w['kills']} kills @ {$w['accuracy']}%\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for w in requests.get('https://wolffiles.eu/api/v1/tracker/players/379/weapons').json()['data']:
    name = (w['weapon'] or {}).get('name', f"bit {w['weapon_bit']}")
    print(f"{name}: {w['kills']} kills @ {w['accuracy']}%")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/players/379/weapons', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/players/{id}/aliases --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-player-aliases' ? null : 'tracker-player-aliases'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/players/{id}/aliases</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Name history</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-player-aliases' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-player-aliases'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns the names a player has used, ordered by frequency. Includes the raw name (with color codes), clean name, and HTML-rendered name.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Tracker player ID <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Number of aliases, max 500 <span class="text-gray-500 text-xs">(optional, default 100)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/players/11/aliases?limit=3</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "name": "^nEts^0|^nT^0o^np^0c^na^0t",
      "name_clean": "Ets|Topcat",
      "name_html": "&lt;span style=\"color:#993300\"&gt;Ets&lt;/span&gt;...",
      "times_used": 171432,
      "first_seen_at": "2026-02-23 17:14:44",
      "last_seen_at": "2026-06-17 08:08:43"
    }
  ],
  "meta": { "player_id": 11, "count": 3, "limit": 3 }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$aliases = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/players/11/aliases?limit=5'
), true)['data'];
foreach ($aliases as $a) {
    echo "{$a['name_clean']} (used {$a['times_used']}x)\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for a in requests.get('https://wolffiles.eu/api/v1/tracker/players/11/aliases', params={'limit': 5}).json()['data']:
    print(f"{a['name_clean']} (used {a['times_used']}x)")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/players/11/aliases?limit=3', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/players/{id}/matches --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-player-matches' ? null : 'tracker-player-matches'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/players/{id}/matches</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Match history for a player</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-player-matches' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-player-matches'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns a player's recent matches (most recent first) with per-match kills/deaths/score/accuracy and server/map context. Paginated via <code class="text-gray-400">offset</code> + <code class="text-gray-400">has_more</code>.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Tracker player ID <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 100 <span class="text-gray-500 text-xs">(optional, default 25)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">offset</code><span class="text-gray-300">Pagination offset <span class="text-gray-500 text-xs">(optional, default 0)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/players/379/matches?limit=2</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "match_id": 33811, "map_name": "tower_b27j",
      "server": { "id": 49163, "name": "&gt;M!&lt; Merged! silEnT" },
      "started_at": "2026-06-17 19:08:12.000",
      "team": null, "class": 0,
      "kills": 1, "deaths": 1, "headshots": 0, "gibs": 63,
      "score": 820, "accuracy_pct": 46.51, "playtime_seconds": 0
    }
  ],
  "meta": { "player_id": 379, "count": 2, "limit": 2, "offset": 0, "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$matches = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/players/379/matches?limit=10'
), true)['data'];
foreach ($matches as $m) {
    echo "{$m['map_name']}: {$m['kills']}/{$m['deaths']} (score {$m['score']})\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for m in requests.get('https://wolffiles.eu/api/v1/tracker/players/379/matches', params={'limit': 10}).json()['data']:
    print(f"{m['map_name']}: {m['kills']}/{m['deaths']} (score {m['score']})")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/players/379/matches?limit=2', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/players/{id}/kills --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-player-kills' ? null : 'tracker-player-kills'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/players/{id}/kills</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Kill feed for a player (RtCW)</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-player-kills' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-player-kills'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns individual kill events for a player. <span class="text-amber-400">RtCW only</span> — ET kills are not stored as individual events (every response carries <code class="text-gray-400">meta.source: "rtcw"</code>; ET players return an empty feed). Use <code class="text-gray-400">role</code> to switch between kills made and deaths suffered.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Tracker player ID <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">role</code><span class="text-gray-300"><code class="text-gray-400">kills</code> or <code class="text-gray-400">deaths</code> <span class="text-gray-500 text-xs">(optional, default kills)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">exclude_bots</code><span class="text-gray-300">Set to <code class="text-gray-400">1</code> to omit bot kills <span class="text-gray-500 text-xs">(optional)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 200 <span class="text-gray-500 text-xs">(optional, default 50)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">offset</code><span class="text-gray-300">Pagination offset <span class="text-gray-500 text-xs">(optional, default 0)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/players/46614/kills?limit=3</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "id": 27147,
      "killer": { "player_id": 46614, "slot": 2, "name": "", "name_clean": "", "is_bot": false },
      "victim": { "player_id": 236, "slot": 0, "name": "WolfPlayer", "name_clean": "WolfPlayer", "is_bot": false },
      "weapon_key": "machinegun", "category": "weapon", "mod_index": 3,
      "is_frag": true, "is_world": false,
      "killed_at": "2026-06-14 18:03:37", "server_id": 26707, "match_id": 25941
    }
  ],
  "meta": { "player_id": 46614, "role": "kills", "source": "rtcw", "count": 3, "limit": 3, "offset": 0, "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$res = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/players/46614/kills?limit=10&exclude_bots=1'
), true);
foreach ($res['data'] as $k) {
    echo "{$k['victim']['name_clean']} ({$k['weapon_key']})\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

res = requests.get('https://wolffiles.eu/api/v1/tracker/players/46614/kills',
                   params={'limit': 10, 'exclude_bots': 1}).json()
for k in res['data']:
    print(f"{k['victim']['name_clean']} ({k['weapon_key']})")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/players/46614/kills?limit=3', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- Section: Tracker — Matches --}}
<h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2 pt-4">⚔️ Tracker API — Matches</h2>

{{-- GET /tracker/matches --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-matches' ? null : 'tracker-matches'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/matches</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Recent matches</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-matches' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-matches'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns recent matches (most recent first) with map, server, duration, and player-count summary. Filter by server or map. Paginated via <code class="text-gray-400">offset</code> + <code class="text-gray-400">has_more</code>.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">server_id</code><span class="text-gray-300">Filter by server <span class="text-gray-500 text-xs">(optional)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">map</code><span class="text-gray-300">Filter by map name <span class="text-gray-500 text-xs">(optional)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 100 <span class="text-gray-500 text-xs">(optional, default 25)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">offset</code><span class="text-gray-300">Pagination offset <span class="text-gray-500 text-xs">(optional, default 0)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/matches?limit=2</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "id": 33812,
      "server": { "id": 26436, "name": "[eG] SNIPER WAR XPS" },
      "map_name": "uje_skull_sniper",
      "started_at": "2026-06-17 19:08:37.000",
      "ended_at": "2026-06-17 19:08:47.000",
      "duration_seconds": 10, "end_reason": "maprestart",
      "players": { "max": 0, "avg": 0, "at_start": null, "at_end": null },
      "totals": { "kills": 0, "deaths": 0 }
    }
  ],
  "meta": { "count": 2, "limit": 2, "offset": 0, "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$matches = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/matches?limit=10'
), true)['data'];
foreach ($matches as $m) {
    echo "{$m['map_name']} on {$m['server']['name']}\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for m in requests.get('https://wolffiles.eu/api/v1/tracker/matches', params={'limit': 10}).json()['data']:
    print(f"{m['map_name']} on {m['server']['name']}")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/matches?limit=2', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/matches/{id} --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-match-detail' ? null : 'tracker-match-detail'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/matches/{id}</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Match detail + full scoreboard</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-match-detail' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-match-detail'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns full match details plus the complete scoreboard (one row per player, ordered by score). Each row carries kills/deaths/gibs/revives/damage/objectives/score/ping. <code class="text-gray-400">team</code> and <code class="text-gray-400">class</code> are raw ET enum integers (may be null for some mods).</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Path Parameter</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Match ID <span class="text-red-400 text-xs">(required)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/matches/33812</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": {
    "match": {
      "id": 33812,
      "server": { "id": 26436, "name": "[eG] SNIPER WAR XPS" },
      "map_name": "uje_skull_sniper",
      "started_at": "...", "ended_at": "...", "duration_seconds": 10,
      "players": {
        "max": 8, "avg": 6,
        "at_start": { "total": 8, "allies": 4, "axis": 4, "spectator": 0 },
        "at_end": { "total": 6, "allies": 3, "axis": 3, "spectator": 0 }
      },
      "totals": { "kills": 42, "deaths": 42 }
    },
    "scoreboard": [
      {
        "player_id": 1475, "name": "...", "name_clean": "EUR2020|GER",
        "slot": 1, "team": 1, "class": 4,
        "kills": 12, "deaths": 8, "headshots": 5, "gibs": 2,
        "damage_given": 1840, "score": 120, "accuracy_pct": 28.5, "ping_avg": 47
      }
    ]
  },
  "meta": { "player_count": 8 }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$d = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/matches/33812'
), true)['data'];
echo "{$d['match']['map_name']} — {$d['meta']['player_count']} players\n";
foreach ($d['scoreboard'] as $p) {
    echo "  {$p['name_clean']}: {$p['kills']}/{$p['deaths']}\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

d = requests.get('https://wolffiles.eu/api/v1/tracker/matches/33812').json()['data']
print(d['match']['map_name'])
for p in d['scoreboard']:
    print(f"  {p['name_clean']}: {p['kills']}/{p['deaths']}")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/matches/33812', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/matches/{id}/weapons --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-match-weapons' ? null : 'tracker-match-weapons'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/matches/{id}/weapons</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Per-player weapon stats in a match</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-match-weapons' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-match-weapons'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns per-weapon stats for every player in a match, grouped by player (ordered by kills). Same weapon metadata as <code class="text-gray-400">/players/{id}/weapons</code>.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Path Parameter</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Match ID <span class="text-red-400 text-xs">(required)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/matches/33812/weapons</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "player_id": 1475, "name": "...", "name_clean": "EUR2020|GER", "kills": 12,
      "weapons": [
        {
          "weapon_bit": 7,
          "weapon": { "name": "FG42", "slug": "fg42", "category": "rifle", "side": "axis", "icon": "..." },
          "hits": 88, "attempts": 210, "kills": 12, "deaths": 8, "headshots": 5, "accuracy": 41.9
        }
      ]
    }
  ],
  "meta": { "match_id": 33812, "player_count": 8 }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$players = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/matches/33812/weapons'
), true)['data'];
foreach ($players as $p) {
    echo "{$p['name_clean']}: " . count($p['weapons']) . " weapons\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for p in requests.get('https://wolffiles.eu/api/v1/tracker/matches/33812/weapons').json()['data']:
    print(f"{p['name_clean']}: {len(p['weapons'])} weapons")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/matches/33812/weapons', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/matches/{id}/kills --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-match-kills' ? null : 'tracker-match-kills'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/matches/{id}/kills</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Chronological kill log (RtCW)</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-match-kills' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-match-kills'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns the kill log of a match in chronological order. <span class="text-amber-400">RtCW only</span> (<code class="text-gray-400">meta.source: "rtcw"</code>; empty for ET matches).</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Match ID <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">exclude_bots</code><span class="text-gray-300">Set to <code class="text-gray-400">1</code> to omit bot kills <span class="text-gray-500 text-xs">(optional)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 1000 <span class="text-gray-500 text-xs">(optional, default 200)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/matches/25941/kills?limit=50</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "id": 38128,
      "killer": { "player_id": null, "slot": 4, "name": null, "is_bot": true },
      "victim": { "player_id": null, "slot": 3, "name": null, "is_bot": true },
      "weapon_key": "mp40", "category": "weapon", "mod_index": 17,
      "is_frag": true, "is_world": false,
      "killed_at": "2026-06-17 19:47:49", "server_id": 26707, "match_id": 33898
    }
  ],
  "meta": { "match_id": 33898, "source": "rtcw", "count": 50, "limit": 50, "offset": 0, "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$kills = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/matches/25941/kills?limit=100'
), true)['data'];
echo count($kills) . " kills in this match\n";</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

kills = requests.get('https://wolffiles.eu/api/v1/tracker/matches/25941/kills', params={'limit': 100}).json()['data']
print(f"{len(kills)} kills in this match")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/matches/25941/kills?limit=50', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- Section: Tracker — Leaderboards --}}
<h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2 pt-4">🏅 Tracker API — Leaderboards</h2>

{{-- GET /tracker/leaderboards --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-leaderboards-index' ? null : 'tracker-leaderboards-index'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/leaderboards</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Available metrics &amp; periods</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-leaderboards-index' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-leaderboards-index'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns the available leaderboard metrics and time periods to use with <code class="text-gray-400">/tracker/leaderboards/{metric}</code>.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/leaderboards</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": {
    "metrics": ["elo", "xp", "kills", "deaths", "playtime", "headshots"],
    "periods": ["alltime", "daily", "weekly", "monthly"],
    "notes": {
      "headshots": "available for the alltime period only",
      "default_period": "alltime"
    }
  }
}</code></pre>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/leaderboards', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/leaderboards/{metric} --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-leaderboard' ? null : 'tracker-leaderboard'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/leaderboards/{metric}</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Ranked players by metric &amp; period</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-leaderboard' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-leaderboard'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns ranked players for a given metric and period. <code class="text-gray-400">alltime</code> uses lifetime totals; <code class="text-gray-400">daily/weekly/monthly</code> use the latest snapshot for that window. Each entry has a <code class="text-gray-400">position</code>, the <code class="text-gray-400">value</code> for the chosen metric, and a full <code class="text-gray-400">stats</code> block.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">metric</code><span class="text-gray-300">Path: <code class="text-gray-400">elo</code>, <code class="text-gray-400">xp</code>, <code class="text-gray-400">kills</code>, <code class="text-gray-400">deaths</code>, <code class="text-gray-400">playtime</code>, <code class="text-gray-400">headshots</code> <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">period</code><span class="text-gray-300"><code class="text-gray-400">alltime</code>, <code class="text-gray-400">daily</code>, <code class="text-gray-400">weekly</code>, <code class="text-gray-400">monthly</code> <span class="text-gray-500 text-xs">(optional, default alltime; headshots = alltime only)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 100 <span class="text-gray-500 text-xs">(optional, default 25)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">offset</code><span class="text-gray-300">Pagination offset <span class="text-gray-500 text-xs">(optional, default 0)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/leaderboards/elo?period=alltime&limit=3</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "position": 1,
      "player": { "id": 1901, "name": "...", "name_clean": "TWH XRP", "name_html": "...", "country_code": "US" },
      "value": 1999.69,
      "stats": { "elo": 1999.69, "xp": 53394000, "kills": 0, "deaths": 0, "playtime_minutes": 173, "headshots": null }
    }
  ],
  "meta": { "metric": "elo", "period": "alltime", "period_date": null, "count": 3, "limit": 3, "offset": 0, "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$board = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/leaderboards/kills?period=weekly&limit=10'
), true)['data'];
foreach ($board as $e) {
    echo "#{$e['position']} {$e['player']['name_clean']} — {$e['value']}\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

board = requests.get('https://wolffiles.eu/api/v1/tracker/leaderboards/kills',
                     params={'period': 'weekly', 'limit': 10}).json()['data']
for e in board:
    print(f"#{e['position']} {e['player']['name_clean']} — {e['value']}")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/leaderboards/elo?limit=3', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- Section: Tracker — Clans --}}
<h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2 pt-4">🛡️ Tracker API — Clans</h2>

{{-- GET /tracker/clans/{id} --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-clan-detail' ? null : 'tracker-clan-detail'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/clans/{id}</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Clan detail</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-clan-detail' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-clan-detail'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns detail for an auto-detected clan: tag, name, description, links, member counts, average ELO, and total playtime.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Path Parameter</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Clan ID <span class="text-red-400 text-xs">(required)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/clans/3</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": {
    "id": 3, "tag": "F|A", "tag_clean": "F|A", "name": "Fearless Assassins",
    "description": "We are glad you decided to stop by...",
    "website": null, "discord": null, "country": null, "country_code": null,
    "members": { "total": 235, "active": 235 },
    "squad_count": 0, "avg_elo": 1000, "total_play_time_minutes": 0,
    "status": "active", "is_verified": false,
    "first_seen_at": "...", "last_seen_at": "..."
  }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$c = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/clans/3'
), true)['data'];
echo "{$c['name']} — {$c['members']['active']} active members\n";</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

c = requests.get('https://wolffiles.eu/api/v1/tracker/clans/3').json()['data']
print(f"{c['name']} — {c['members']['active']} active members")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/clans/3', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/clans/{id}/members --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-clan-members' ? null : 'tracker-clan-members'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/clans/{id}/members</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Clan members with roles</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-clan-members' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-clan-members'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns clan members ordered by role (founder → leader → officer → member), each with player info, role, and squad reference. Only active members by default.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Clan ID <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">squad_id</code><span class="text-gray-300">Filter to one squad <span class="text-gray-500 text-xs">(optional)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">include_inactive</code><span class="text-gray-300">Set to <code class="text-gray-400">1</code> to include former members <span class="text-gray-500 text-xs">(optional)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 500 <span class="text-gray-500 text-xs">(optional, default 100)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/clans/3/members?limit=3</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "player": { "id": 1681, "name": "=F|A= Moses", "name_clean": "=F|A= Moses", "name_html": "...", "country_code": "US", "elo": 1405.96 },
      "role": "member", "role_label": "Member",
      "squad": null,
      "joined_at": "2026-06-11 15:59:24", "left_at": null, "is_active": true
    }
  ],
  "meta": { "clan_id": 3, "count": 3, "limit": 3, "offset": 0, "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$members = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/clans/3/members?limit=50'
), true)['data'];
foreach ($members as $m) {
    echo "{$m['player']['name_clean']} — {$m['role']}\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for m in requests.get('https://wolffiles.eu/api/v1/tracker/clans/3/members', params={'limit': 50}).json()['data']:
    print(f"{m['player']['name_clean']} — {m['role']}")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/clans/3/members?limit=3', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/clans/{id}/squads --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-clan-squads' ? null : 'tracker-clan-squads'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/clans/{id}/squads</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Clan squads with member counts</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-clan-squads' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-clan-squads'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns the squads defined within a clan, each with its active member count. Empty for clans without squads.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Path Parameter</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Clan ID <span class="text-red-400 text-xs">(required)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/clans/3/squads</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    { "id": 7, "name": "Main Squad", "description": "Core roster", "member_count": 12 }
  ],
  "meta": { "clan_id": 3, "count": 1 }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$squads = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/clans/3/squads'
), true)['data'];
foreach ($squads as $s) {
    echo "{$s['name']}: {$s['member_count']} members\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for s in requests.get('https://wolffiles.eu/api/v1/tracker/clans/3/squads').json()['data']:
    print(f"{s['name']}: {s['member_count']} members")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/clans/3/squads', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- Section: Tracker — Maps & Servers (extended) --}}
<h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2 pt-4">🗺️ Tracker API — Maps</h2>

{{-- GET /tracker/maps --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-maps-list' ? null : 'tracker-maps-list'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/maps</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Map list with play stats</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-maps-list' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-maps-list'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns maps with aggregate play stats (computed live across all servers): servers, times played, total minutes, peak players. Sortable and searchable.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">sort</code><span class="text-gray-300"><code class="text-gray-400">time</code>, <code class="text-gray-400">played</code>, <code class="text-gray-400">servers</code>, <code class="text-gray-400">peak</code>, <code class="text-gray-400">recent</code> <span class="text-gray-500 text-xs">(optional, default time)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">q</code><span class="text-gray-300">Search by map name <span class="text-gray-500 text-xs">(optional)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 200 <span class="text-gray-500 text-xs">(optional, default 50)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">offset</code><span class="text-gray-300">Pagination offset <span class="text-gray-500 text-xs">(optional, default 0)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/maps?sort=time&limit=3</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "id": 17, "name": "oasis", "name_clean": "oasis",
      "file_id": null, "screenshot_path": null,
      "stats": {
        "servers": 1576, "times_played": 110860,
        "time_played_minutes": 3374032, "peak_players": 66,
        "last_played_at": "2026-06-17 19:35:48"
      },
      "first_seen_at": "...", "last_seen_at": "..."
    }
  ],
  "meta": { "count": 3, "limit": 3, "offset": 0, "sort": "time", "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$maps = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/maps?sort=time&limit=10'
), true)['data'];
foreach ($maps as $m) {
    echo "{$m['name']}: {$m['stats']['servers']} servers\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for m in requests.get('https://wolffiles.eu/api/v1/tracker/maps', params={'sort': 'time', 'limit': 10}).json()['data']:
    print(f"{m['name']}: {m['stats']['servers']} servers")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/maps?sort=time&limit=3', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/maps/{name}/stats --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-map-detail' ? null : 'tracker-map-detail'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/maps/{name}/stats</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Map detail + top servers</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-map-detail' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-map-detail'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns aggregate stats for one map plus the top servers that run it (by total time). Note: this is the <em>historical</em> stats endpoint — for "who is playing this map right now", use <code class="text-gray-400">/tracker/maps/{mapName}</code>.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">name</code><span class="text-gray-300">Map name (e.g. <code class="text-gray-400">goldrush-gals</code>) <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Top servers to return, max 50 <span class="text-gray-500 text-xs">(optional, default 10)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/maps/goldrush-gals/stats</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": {
    "map": {
      "id": 151, "name": "goldrush-gals", "name_clean": "goldrush-gals",
      "file_id": 1881, "screenshot_path": null,
      "stats": { "servers": 53, "times_played": 9027, "time_played_minutes": 263750, "peak_players": 60, "last_played_at": "..." },
      "first_seen_at": "...", "last_seen_at": "..."
    },
    "top_servers": [
      {
        "server": { "id": 15466, "name": "[!!!]Hirntot Legacy" },
        "times_played": 506, "total_time_minutes": 35554,
        "avg_players": 10.47, "peak_players": 43, "last_played_at": "..."
      }
    ]
  },
  "meta": { "server_count": 10 }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$d = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/maps/goldrush-gals/stats'
), true)['data'];
echo "{$d['map']['name']}: {$d['map']['stats']['servers']} servers\n";</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

d = requests.get('https://wolffiles.eu/api/v1/tracker/maps/goldrush-gals/stats').json()['data']
print(f"{d['map']['name']}: {d['map']['stats']['servers']} servers")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/maps/goldrush-gals/stats', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/servers/{id}/maps --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-server-maps' ? null : 'tracker-server-maps'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/servers/{id}/maps</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Map breakdown for a server</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-server-maps' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-server-maps'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns the maps a server runs most, ordered by total time. Includes times played, total minutes, average and peak players, and last played time.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Server ID <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 200 <span class="text-gray-500 text-xs">(optional, default 50)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">offset</code><span class="text-gray-300">Pagination offset <span class="text-gray-500 text-xs">(optional, default 0)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/servers/152/maps?limit=3</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "map_name": "goldrush-gals", "times_played": 388,
      "total_time_minutes": 12552, "avg_players": 42.67,
      "peak_players": 60, "last_played_at": "2026-06-17 19:28:32"
    }
  ],
  "meta": { "server_id": 152, "count": 3, "limit": 3, "offset": 0, "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$maps = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/servers/152/maps?limit=10'
), true)['data'];
foreach ($maps as $m) {
    echo "{$m['map_name']}: {$m['times_played']}x\n";
}</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

for m in requests.get('https://wolffiles.eu/api/v1/tracker/servers/152/maps', params={'limit': 10}).json()['data']:
    print(f"{m['map_name']}: {m['times_played']}x")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/servers/152/maps?limit=3', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>

{{-- GET /tracker/servers/{id}/kills --}}
<div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
    <button @click="openEndpoint = openEndpoint === 'tracker-server-kills' ? null : 'tracker-server-kills'"
        class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
        <div class="flex items-center gap-3">
            <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
            <code class="text-white text-sm">/tracker/servers/{id}/kills</code>
            <span class="text-gray-500 text-sm hidden sm:inline">— Kill feed for a server (RtCW)</span>
        </div>
        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tracker-server-kills' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="openEndpoint === 'tracker-server-kills'" x-collapse x-cloak class="border-t border-gray-700 p-5 space-y-4">
        <p class="text-gray-400 text-sm">Returns the recent kill feed for a server (most recent first). <span class="text-amber-400">RtCW only</span> (<code class="text-gray-400">meta.source: "rtcw"</code>; empty for ET servers). Same kill shape as the player kill feed.</p>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Parameters</h4>
            <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">Server ID <span class="text-red-400 text-xs">(required)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">exclude_bots</code><span class="text-gray-300">Set to <code class="text-gray-400">1</code> to omit bot kills <span class="text-gray-500 text-xs">(optional)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">Results per page, max 200 <span class="text-gray-500 text-xs">(optional, default 50)</span></span></div>
                <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">offset</code><span class="text-gray-300">Pagination offset <span class="text-gray-500 text-xs">(optional, default 0)</span></span></div>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Example Request</h4>
            <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tracker/servers/26707/kills?limit=3</code>
            </div>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Response</h4>
            <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto"><code class="text-gray-300">{
  "data": [
    {
      "id": 38134,
      "killer": { "player_id": null, "slot": 5, "name": null, "is_bot": true },
      "victim": { "player_id": null, "slot": 3, "name": null, "is_bot": true },
      "weapon_key": "colt", "category": "weapon", "mod_index": 16,
      "is_frag": true, "is_world": false,
      "killed_at": "2026-06-17 19:48:52", "server_id": 26707, "match_id": 33898
    }
  ],
  "meta": { "server_id": 26707, "source": "rtcw", "count": 3, "limit": 3, "offset": 0, "has_more": true }
}</code></pre>
        </div>
        <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Code</h4>
            <div x-data="{ lang: 'php' }">
                <div class="flex gap-2 mb-3 flex-wrap">
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1 rounded text-xs font-medium transition">Python</button>
                </div>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$kills = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/tracker/servers/26707/kills?limit=20&exclude_bots=1'
), true)['data'];
echo count($kills) . " recent kills\n";</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

kills = requests.get('https://wolffiles.eu/api/v1/tracker/servers/26707/kills',
                     params={'limit': 20, 'exclude_bots': 1}).json()['data']
print(f"{len(kills)} recent kills")</code></pre>
            </div>
        </div>
        <button onclick="testEndpoint('/api/v1/tracker/servers/26707/kills?limit=3', this)"
            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
            ▶ Try it
        </button>
    </div>
</div>