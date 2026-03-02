<x-layouts.app title="API Documentation">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-amber-400 mb-2">🔌 Wolffiles API</h1>
            <p class="text-gray-400">Free public REST API for Wolfenstein: Enemy Territory file data. No authentication required.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <span class="bg-green-500/20 text-green-400 px-3 py-1 rounded-full text-xs font-medium">✅ Free</span>
                <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-xs font-medium">🔓 No Auth Required</span>
                <span class="bg-amber-500/20 text-amber-400 px-3 py-1 rounded-full text-xs font-medium">📦 JSON Responses</span>
            </div>
        </div>

        {{-- Base URL --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-400 mb-2">BASE URL</h2>
            <code class="text-lg text-amber-400 font-mono">https://wolffiles.eu/api/v1</code>
        </div>

        {{-- Quick Links --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
            <a href="#files-search" class="bg-gray-800 rounded-lg p-3 border border-gray-700 hover:border-amber-500 transition text-center">
                <div class="text-lg mb-1">🔍</div>
                <div class="text-xs text-gray-300">Search</div>
            </a>
            <a href="#files-latest" class="bg-gray-800 rounded-lg p-3 border border-gray-700 hover:border-amber-500 transition text-center">
                <div class="text-lg mb-1">🆕</div>
                <div class="text-xs text-gray-300">Latest</div>
            </a>
            <a href="#files-top" class="bg-gray-800 rounded-lg p-3 border border-gray-700 hover:border-amber-500 transition text-center">
                <div class="text-lg mb-1">🏆</div>
                <div class="text-xs text-gray-300">Top</div>
            </a>
            <a href="#stats" class="bg-gray-800 rounded-lg p-3 border border-gray-700 hover:border-amber-500 transition text-center">
                <div class="text-lg mb-1">📊</div>
                <div class="text-xs text-gray-300">Stats</div>
            </a>
        </div>

        {{-- Endpoints --}}
        <div class="space-y-6" x-data="{ openEndpoint: null }">

            {{-- Section: Files --}}
            <h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2">📁 Files</h2>

            {{-- GET /files/search --}}
            <div id="files-search" class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'search' ? null : 'search'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/search</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Search files by keyword</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'search' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'search'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Search through all approved files. Returns matching files with metadata.</p>
                    <h4 class="text-xs font-semibold text-gray-500 mb-2">PARAMETERS</h4>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 text-sm space-y-2">
                        <div class="flex gap-3"><code class="text-amber-400 w-20">q</code><span class="text-gray-300">Search query <span class="text-red-400">(required)</span></span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-20">limit</code><span class="text-gray-300">Max results (default: 10, max: 50)</span></div>
                    </div>
                    <h4 class="text-xs font-semibold text-gray-500 mb-2">EXAMPLE REQUEST</h4>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/search?q=goldrush&limit=5</code>
                    </div>
                    <h4 class="text-xs font-semibold text-gray-500 mb-2">EXAMPLE RESPONSE</h4>
                    <pre class="bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto text-gray-300"><code>{
  "files": [
    {
      "id": 42,
      "title": "Goldrush",
      "slug": "goldrush",
      "category": "Maps",
      "game": "ET",
      "file_size": "4.2 MB",
      "download_count": 15234,
      "average_rating": 4.5,
      "url": "https://wolffiles.eu/files/goldrush",
      "download_url": "https://wolffiles.eu/files/goldrush/download",
      "thumbnail": "https://wolffiles.fsn1.your-objectstorage.com/...",
      "author": "Activision",
      "published_at": "2003-05-29T00:00:00+00:00"
    }
  ],
  "total": 1
}</code></pre>
                    <div class="mt-3">
                        <button onclick="testEndpoint('/api/v1/files/search?q=goldrush&limit=3', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                    </div>
                </div>
            </div>

            {{-- GET /files/latest --}}
            <div id="files-latest" class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'latest' ? null : 'latest'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/latest</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Get newest uploaded files</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'latest' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'latest'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Returns the most recently uploaded and approved files.</p>
                    <h4 class="text-xs font-semibold text-gray-500 mb-2">PARAMETERS</h4>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 text-sm">
                        <div class="flex gap-3"><code class="text-amber-400 w-20">limit</code><span class="text-gray-300">Max results (default: 10, max: 50)</span></div>
                    </div>
                    <h4 class="text-xs font-semibold text-gray-500 mb-2">EXAMPLE</h4>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/latest?limit=5</code>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/latest?limit=3', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                </div>
            </div>

            {{-- GET /files/random --}}
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'random' ? null : 'random'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/random</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Get a random file</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'random' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'random'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Returns a single random approved file. Great for "discover" features.</p>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/random</code>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/random', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                </div>
            </div>

            {{-- GET /files/top --}}
            <div id="files-top" class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'top' ? null : 'top'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/top</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Most downloaded files</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'top' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'top'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Returns files sorted by download count.</p>
                    <h4 class="text-xs font-semibold text-gray-500 mb-2">PARAMETERS</h4>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 text-sm space-y-2">
                        <div class="flex gap-3"><code class="text-amber-400 w-20">limit</code><span class="text-gray-300">Max results (default: 10, max: 50)</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-20">period</code><span class="text-gray-300">Time period: <code class="text-gray-500">all</code>, <code class="text-gray-500">month</code>, <code class="text-gray-500">week</code></span></div>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/top?limit=5&period=month</code>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/top?limit=3', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                </div>
            </div>

            {{-- GET /files/trending --}}
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'trending' ? null : 'trending'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/trending</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Currently trending files</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'trending' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'trending'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Returns files with highest trending score based on recent activity (downloads, comments, views).</p>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 text-sm">
                        <div class="flex gap-3"><code class="text-amber-400 w-20">limit</code><span class="text-gray-300">Max results (default: 10, max: 50)</span></div>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/trending?limit=5</code>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/trending?limit=3', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                </div>
            </div>

            {{-- GET /files/featured --}}
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'featured' ? null : 'featured'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/featured</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Editor's pick / featured file</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'featured' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'featured'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Returns the currently featured (editor's pick) file.</p>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/featured</code>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/featured', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                </div>
            </div>

            {{-- GET /files/{slug} --}}
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'show' ? null : 'show'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/{id}</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Get single file details</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'show' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'show'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Returns full details for a specific file including readme content.</p>
                    <h4 class="text-xs font-semibold text-gray-500 mb-2">PATH PARAMETERS</h4>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 text-sm">
                        <div class="flex gap-3"><code class="text-amber-400 w-20">id</code><span class="text-gray-300">File ID <span class="text-red-400">(required)</span></span></div>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/42</code>
                    </div>
                    <h4 class="text-xs font-semibold text-gray-500 mb-2">RESPONSE FIELDS</h4>
                    <div class="bg-gray-900 rounded-lg p-3 text-xs space-y-1">
                        <div class="flex gap-3"><code class="text-amber-400 w-32">id</code><span class="text-gray-400">integer</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">title</code><span class="text-gray-400">string</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">slug</code><span class="text-gray-400">string</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">category</code><span class="text-gray-400">string</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">game</code><span class="text-gray-400">string — ET, RtCW, etc.</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">map_name</code><span class="text-gray-400">string | null</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">file_size</code><span class="text-gray-400">string — "4.2 MB"</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">download_count</code><span class="text-gray-400">integer</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">average_rating</code><span class="text-gray-400">float — 0.0 to 5.0</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">url</code><span class="text-gray-400">string — Web page URL</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">download_url</code><span class="text-gray-400">string — Direct download URL</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">thumbnail</code><span class="text-gray-400">string | null — Image URL</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">author</code><span class="text-gray-400">string | null</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">published_at</code><span class="text-gray-400">ISO 8601 datetime</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">mod_compatibility</code><span class="text-gray-400">string | null</span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-32">readme_content</code><span class="text-gray-400">string | null — Extracted README</span></div>
                    </div>
                </div>
            </div>

            {{-- Section: Stats --}}
            <h2 id="stats" class="text-xl font-bold text-white border-b border-gray-700 pb-2 mt-8">📊 Statistics</h2>

            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'stats' ? null : 'stats'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/stats</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Site-wide statistics</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'stats' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'stats'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Returns aggregate statistics about the Wolffiles platform.</p>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/stats</code>
                    </div>
                    <button onclick="testEndpoint('/api/v1/stats', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                </div>
            </div>

            {{-- Section: Wiki & Tutorials --}}
            <h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2 mt-8">📖 Wiki & Tutorials</h2>

            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'wiki' ? null : 'wiki'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/wiki/search</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Search wiki articles</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'wiki' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'wiki'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Search through wiki articles about ET gameplay, mapping, modding etc.</p>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 text-sm">
                        <div class="flex gap-3"><code class="text-amber-400 w-20">q</code><span class="text-gray-300">Search query <span class="text-red-400">(required)</span></span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-20">limit</code><span class="text-gray-300">Max results (default: 10)</span></div>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/wiki/search?q=mapping</code>
                    </div>
                    <button onclick="testEndpoint('/api/v1/wiki/search?q=map&limit=3', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'tutorials' ? null : 'tutorials'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-750 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/tutorials/search</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— Search tutorials</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition" :class="openEndpoint === 'tutorials' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'tutorials'" x-collapse class="border-t border-gray-700 p-4">
                    <p class="text-gray-400 text-sm mb-4">Search through ET tutorials and guides.</p>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 text-sm">
                        <div class="flex gap-3"><code class="text-amber-400 w-20">q</code><span class="text-gray-300">Search query <span class="text-red-400">(required)</span></span></div>
                        <div class="flex gap-3"><code class="text-amber-400 w-20">limit</code><span class="text-gray-300">Max results (default: 10)</span></div>
                    </div>
                    <div class="bg-gray-900 rounded-lg p-3 mb-4 overflow-x-auto">
                        <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tutorials/search?q=install</code>
                    </div>
                    <button onclick="testEndpoint('/api/v1/tutorials/search?q=install&limit=3', this)" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition">▶ Try it</button>
                </div>
            </div>

        </div>

        {{-- Rate Limits --}}
        <div class="mt-8 bg-gray-800 rounded-xl border border-gray-700 p-6">
            <h2 class="text-lg font-bold text-white mb-3">⚠️ Rate Limits & Usage</h2>
            <div class="text-sm text-gray-400 space-y-2">
                <p>The API is rate-limited to prevent abuse. Please be respectful with your usage.</p>
                <p>If you need higher limits or custom endpoints for your project, contact us at <a href="{{ route('contact') }}" class="text-amber-400 hover:text-amber-300">wolffiles.eu/contact</a>.</p>
            </div>
        </div>

        {{-- Code Examples --}}
        <div class="mt-6 bg-gray-800 rounded-xl border border-gray-700 p-6">
            <h2 class="text-lg font-bold text-white mb-4">💻 Code Examples</h2>

            <div x-data="{ lang: 'curl' }">
                <div class="flex gap-2 mb-4">
                    <button @click="lang = 'curl'" :class="lang === 'curl' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300'" class="px-3 py-1 rounded text-xs">cURL</button>
                    <button @click="lang = 'js'" :class="lang === 'js' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300'" class="px-3 py-1 rounded text-xs">JavaScript</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300'" class="px-3 py-1 rounded text-xs">Python</button>
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300'" class="px-3 py-1 rounded text-xs">PHP</button>
                </div>

                <pre x-show="lang === 'curl'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">curl -s https://wolffiles.eu/api/v1/files/search?q=goldrush | json_pp</code></pre>

                <pre x-show="lang === 'js'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">const response = await fetch('https://wolffiles.eu/api/v1/files/search?q=goldrush');
const data = await response.json();
console.log(data.files);</code></pre>

                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

response = requests.get('https://wolffiles.eu/api/v1/files/search', params={'q': 'goldrush'})
data = response.json()
for f in data['files']:
    print(f'{f["title"]} — {f["download_count"]} downloads')</code></pre>

                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$response = file_get_contents('https://wolffiles.eu/api/v1/files/search?q=goldrush');
$data = json_decode($response, true);
foreach ($data['files'] as $file) {
    echo $file['title'] . ' — ' . $file['download_count'] . " downloads\n";
}</code></pre>
            </div>
        </div>

        {{-- Response area for Try It --}}
        <div id="api-response-area" class="mt-6 hidden">
            <div class="bg-gray-800 rounded-xl border border-amber-600/50 p-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-semibold text-amber-400">API Response</h3>
                    <button onclick="document.getElementById('api-response-area').classList.add('hidden')" class="text-gray-500 hover:text-white text-sm">✕ Close</button>
                </div>
                <pre id="api-response" class="bg-gray-900 rounded-lg p-4 text-xs overflow-x-auto text-gray-300 max-h-96"></pre>
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>Wolffiles API v1 · Built with ❤️ for the ET Community</p>
            <p class="mt-1">Questions? <a href="{{ route('contact') }}" class="text-amber-400 hover:text-amber-300">Contact us</a> · <a href="https://discord.gg/wolffiles" class="text-amber-400 hover:text-amber-300">Join Discord</a></p>
        </div>
    </div>

    <script>
    function testEndpoint(url, btn) {
        const area = document.getElementById('api-response-area');
        const pre = document.getElementById('api-response');
        area.classList.remove('hidden');
        pre.textContent = 'Loading...';
        btn.disabled = true;
        btn.textContent = '⏳ Loading...';

        fetch(url)
            .then(r => r.json())
            .then(data => {
                pre.textContent = JSON.stringify(data, null, 2);
                area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(e => {
                pre.textContent = 'Error: ' + e.message;
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '▶ Try it';
            });
    }
    </script>
</x-layouts.app>
