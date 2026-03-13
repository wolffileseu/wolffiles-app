<x-layouts.app :title="__('messages.api_title')">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-amber-400 mb-2">🔌 {{ __('messages.api_title') }}</h1>
            <p class="text-gray-400">{{ __('messages.api_subtitle') }} {{ __('messages.api_no_auth') }}</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <span class="bg-green-500/20 text-green-400 px-3 py-1 rounded-full text-xs font-medium">✅ {{ __('messages.api_free') }}</span>
                <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-xs font-medium">🔓 {{ __('messages.api_no_auth_badge') }}</span>
                <span class="bg-amber-500/20 text-amber-400 px-3 py-1 rounded-full text-xs font-medium">📦 {{ __('messages.api_json') }}</span>
                <span class="bg-purple-500/20 text-purple-400 px-3 py-1 rounded-full text-xs font-medium">⚡ 60 req/min</span>
            </div>
        </div>

        {{-- Base URL --}}
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6">
            <h2 class="text-xs font-semibold text-gray-400 mb-2 uppercase tracking-widest">{{ __('messages.api_base_url') }}</h2>
            <code class="text-lg text-amber-400 font-mono">https://wolffiles.eu/api/v1</code>
        </div>

        {{-- Quick Links --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-8">
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
            <a href="#section-stats" class="bg-gray-800 rounded-lg p-3 border border-gray-700 hover:border-amber-500 transition text-center">
                <div class="text-lg mb-1">📊</div>
                <div class="text-xs text-gray-300">Stats</div>
            </a>
            <a href="#section-wiki" class="bg-gray-800 rounded-lg p-3 border border-gray-700 hover:border-amber-500 transition text-center">
                <div class="text-lg mb-1">📖</div>
                <div class="text-xs text-gray-300">Wiki</div>
            </a>
        </div>

        {{-- Endpoints --}}
        <div class="space-y-4" x-data="{ openEndpoint: null }">

            {{-- Section: Files --}}
            <h2 class="text-xl font-bold text-white border-b border-gray-700 pb-2">📁 {{ __('messages.api_section_files') }}</h2>

            {{-- GET /files/search --}}
            <div id="files-search" class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'search' ? null : 'search'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/search</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_file_search') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'search' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'search'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_search_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_parameters') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">q</code><span class="text-gray-300">{{ __('messages.api_param_q') }} <span class="text-red-400 text-xs">({{ __('messages.api_required') }})</span></span></div>
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">game</code><span class="text-gray-300">{{ __('messages.api_param_game') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">category</code><span class="text-gray-300">{{ __('messages.api_param_category') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">{{ __('messages.api_param_limit') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/search?q=goldrush&limit=5</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/search?q=goldrush&limit=3', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            {{-- GET /files/latest --}}
            <div id="files-latest" class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'latest' ? null : 'latest'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/latest</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_file_latest') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'latest' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'latest'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_latest_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_parameters') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">game</code><span class="text-gray-300">{{ __('messages.api_param_game') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">{{ __('messages.api_param_limit') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/latest?limit=5</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/latest?limit=3', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            {{-- GET /files/random --}}
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'random' ? null : 'random'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/random</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_file_random') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'random' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'random'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_random_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_parameters') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-sm">
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">game</code><span class="text-gray-300">{{ __('messages.api_param_game') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/random</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/random', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            {{-- GET /files/top --}}
            <div id="files-top" class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'top' ? null : 'top'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/top</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_file_top') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'top' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'top'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_top_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_parameters') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">period</code><span class="text-gray-300">{{ __('messages.api_param_period') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">{{ __('messages.api_param_limit') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/top?period=month&limit=5</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/top?limit=3&period=month', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            {{-- GET /files/trending --}}
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'trending' ? null : 'trending'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/trending</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_file_trending') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'trending' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'trending'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_trending_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_parameters') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-sm">
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">{{ __('messages.api_param_limit') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/trending?limit=5</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/trending?limit=3', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            {{-- GET /files/featured --}}
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'featured' ? null : 'featured'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/featured</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_file_featured') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'featured' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'featured'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_featured_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/featured</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/featured', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            {{-- GET /files/{id} --}}
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'show' ? null : 'show'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/files/{id}</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_file_show') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'show' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'show'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_show_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_path_params') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-sm">
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">id</code><span class="text-gray-300">{{ __('messages.api_param_id') }} <span class="text-red-400 text-xs">({{ __('messages.api_required') }})</span></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_response_fields') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-xs space-y-1.5 font-mono">
                            @foreach(['id' => 'integer', 'title' => 'string', 'slug' => 'string', 'category' => 'string', 'game' => 'string — ET, RtCW', 'map_name' => 'string|null', 'file_size' => 'string — "4.2 MB"', 'download_count' => 'integer', 'average_rating' => 'float — 0.0–5.0', 'url' => 'string', 'download_url' => 'string', 'thumbnail' => 'string|null', 'author' => 'string|null', 'published_at' => 'ISO 8601', 'mod_compatibility' => 'string|null', 'readme_content' => 'string|null', 'description' => 'string|null', 'version' => 'string|null', 'tags' => 'array', 'screenshots' => 'array'] as $field => $type)
                            <div class="flex gap-3"><code class="text-amber-400 w-36 shrink-0">{{ $field }}</code><span class="text-gray-500">{{ $type }}</span></div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/files/1</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/files/1', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            {{-- Section: Stats --}}
            <h2 id="section-stats" class="text-xl font-bold text-white border-b border-gray-700 pb-2 pt-4">📊 {{ __('messages.api_section_stats') }}</h2>

            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'stats' ? null : 'stats'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/stats</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_stats') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'stats' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'stats'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_stats_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/stats</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/stats', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            {{-- Section: Wiki & Tutorials --}}
            <h2 id="section-wiki" class="text-xl font-bold text-white border-b border-gray-700 pb-2 pt-4">📖 {{ __('messages.api_section_wiki') }}</h2>

            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'wiki' ? null : 'wiki'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/wiki/search</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_wiki_search') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'wiki' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'wiki'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_wiki_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_parameters') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">q</code><span class="text-gray-300">{{ __('messages.api_param_q') }} <span class="text-red-400 text-xs">({{ __('messages.api_required') }})</span></span></div>
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">{{ __('messages.api_param_limit') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/wiki/search?q=mapping</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/wiki/search?q=map&limit=3', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <button @click="openEndpoint = openEndpoint === 'tutorials' ? null : 'tutorials'"
                    class="w-full flex items-center justify-between p-4 hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <span class="bg-green-500/20 text-green-400 px-2.5 py-0.5 rounded text-xs font-mono font-bold">GET</span>
                        <code class="text-white text-sm">/tutorials/search</code>
                        <span class="text-gray-500 text-sm hidden sm:inline">— {{ __('messages.api_get_tutorials_search') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openEndpoint === 'tutorials' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openEndpoint === 'tutorials'" x-collapse class="border-t border-gray-700 p-5 space-y-4">
                    <p class="text-gray-400 text-sm">{{ __('messages.api_tutorials_desc') }}</p>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_parameters') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 text-sm space-y-2">
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">q</code><span class="text-gray-300">{{ __('messages.api_param_q') }} <span class="text-red-400 text-xs">({{ __('messages.api_required') }})</span></span></div>
                            <div class="flex gap-3 items-start"><code class="text-amber-400 w-24 shrink-0">limit</code><span class="text-gray-300">{{ __('messages.api_param_limit') }} <span class="text-gray-500 text-xs">({{ __('messages.api_optional') }})</span></span></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('messages.api_example_request') }}</h4>
                        <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto">
                            <code class="text-green-400 text-sm">GET https://wolffiles.eu/api/v1/tutorials/search?q=install</code>
                        </div>
                    </div>
                    <button onclick="testEndpoint('/api/v1/tutorials/search?q=install&limit=3', this)"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition font-medium">
                        ▶ {{ __('messages.api_try_it') }}
                    </button>
                </div>
            </div>

        </div>

        {{-- Rate Limits --}}
        <div class="mt-8 bg-gray-800 rounded-xl border border-amber-500/20 p-6">
            <h2 class="text-lg font-bold text-white mb-3">⚠️ {{ __('messages.api_rate_limit_title') }}</h2>
            <p class="text-sm text-gray-400 mb-2">{{ __('messages.api_rate_limit_desc') }}</p>
            <p class="text-sm text-gray-400">{{ __('messages.api_rate_limit_contact') }}
                <a href="{{ route('contact') }}" class="text-amber-400 hover:text-amber-300 underline">{{ __('messages.contact') }}</a>
            </p>
        </div>

        {{-- Code Examples --}}
        <div class="mt-6 bg-gray-800 rounded-xl border border-gray-700 p-6">
            <h2 class="text-lg font-bold text-white mb-4">💻 {{ __('messages.api_code_examples') }}</h2>
            <div x-data="{ lang: 'curl' }">
                <div class="flex gap-2 mb-4 flex-wrap">
                    <button @click="lang = 'curl'" :class="lang === 'curl' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1.5 rounded text-xs font-medium transition">cURL</button>
                    <button @click="lang = 'js'" :class="lang === 'js' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1.5 rounded text-xs font-medium transition">JavaScript</button>
                    <button @click="lang = 'python'" :class="lang === 'python' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1.5 rounded text-xs font-medium transition">Python</button>
                    <button @click="lang = 'php'" :class="lang === 'php' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1.5 rounded text-xs font-medium transition">PHP</button>
                    <button @click="lang = 'discord'" :class="lang === 'discord' ? 'bg-amber-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'" class="px-3 py-1.5 rounded text-xs font-medium transition">Discord Bot</button>
                </div>
                <pre x-show="lang === 'curl'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">curl -s "https://wolffiles.eu/api/v1/files/search?q=goldrush" | python3 -m json.tool</code></pre>
                <pre x-show="lang === 'js'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">const res = await fetch('https://wolffiles.eu/api/v1/files/search?q=goldrush');
const data = await res.json();
data.results.forEach(f => console.log(`${f.title} — ${f.download_count} downloads`));</code></pre>
                <pre x-show="lang === 'python'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">import requests

r = requests.get('https://wolffiles.eu/api/v1/files/search', params={'q': 'goldrush', 'limit': 5})
for f in r.json()['results']:
    print(f"{f['title']} — {f['download_count']} downloads")</code></pre>
                <pre x-show="lang === 'php'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">$data = json_decode(file_get_contents(
    'https://wolffiles.eu/api/v1/files/search?q=goldrush'
), true);
foreach ($data['results'] as $file) {
    echo $file['title'] . ' — ' . $file['download_count'] . " downloads\n";
}</code></pre>
                <pre x-show="lang === 'discord'" class="bg-gray-900 rounded-lg p-4 text-sm overflow-x-auto"><code class="text-green-400">// Discord.js v14 slash command example
const res = await fetch('https://wolffiles.eu/api/v1/files/search?q=' + query);
const { results } = await res.json();
const embed = new EmbedBuilder()
    .setTitle(results[0].title)
    .setURL(results[0].url)
    .addFields(
        { name: 'Downloads', value: String(results[0].download_count), inline: true },
        { name: 'Game', value: results[0].game ?? 'ET', inline: true }
    );
await interaction.reply({ embeds: [embed] });</code></pre>
            </div>
        </div>

        {{-- Try It Response Area --}}
        <div id="api-response-area" style="display:none" class="mt-6">
            <div class="bg-gray-800 rounded-xl border border-amber-600/50 p-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-semibold text-amber-400">{{ __('messages.api_response') }}</h3>
                    <button onclick="document.getElementById('api-response-area').style.display='none'"
                        class="text-gray-500 hover:text-white text-sm px-2 py-1 rounded hover:bg-gray-700 transition">
                        ✕ {{ __('messages.api_close') }}
                    </button>
                </div>
                <pre id="api-response" class="bg-gray-900 rounded-lg p-4 text-xs overflow-x-auto text-gray-300 max-h-96 overflow-y-auto"></pre>
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-sm text-gray-500 pb-4">
            <p>{{ __('messages.api_footer') }}</p>
            <p class="mt-1">
                {{ __('messages.api_questions') }}
                <a href="{{ route('contact') }}" class="text-amber-400 hover:text-amber-300">{{ __('messages.contact') }}</a>
                · <a href="https://discord.gg/wolffiles" class="text-amber-400 hover:text-amber-300">{{ __('messages.api_join_discord') }}</a>
            </p>
        </div>

    </div>

    <script>
    function testEndpoint(url, btn) {
        const area = document.getElementById('api-response-area');
        const pre  = document.getElementById('api-response');
        area.style.display = 'block';
        pre.textContent = '{{ __('messages.api_loading') }}';
        btn.disabled = true;
        const orig = btn.textContent;
        btn.textContent = '⏳ {{ __('messages.api_loading') }}';
        fetch(url)
            .then(r => r.json())
            .then(data => {
                pre.textContent = JSON.stringify(data, null, 2);
                area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(e => { pre.textContent = 'Error: ' + e.message; })
            .finally(() => { btn.disabled = false; btn.textContent = orig; });
    }
    </script>
</x-layouts.app>
