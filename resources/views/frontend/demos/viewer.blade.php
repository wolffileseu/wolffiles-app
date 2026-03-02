<x-layouts.app :title="'Viewer: ' . $demo->title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb --}}
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400">Home</a> /
            <a href="{{ route('demos.index') }}" class="hover:text-amber-400">Demos</a> /
            <a href="{{ route('demos.show', $demo) }}" class="hover:text-amber-400">{{ Str::limit($demo->title, 30) }}</a> /
            <span class="text-gray-300">Viewer</span>
        </nav>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-white">🔬 Demo Viewer</h1>
                <p class="text-gray-400 text-sm mt-1">{{ $demo->title }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('demos.show', $demo) }}" class="bg-gray-700 hover:bg-gray-600 text-gray-300 px-4 py-2 rounded-lg text-sm">← Back</a>
                <a href="{{ route('demos.download', $demo) }}" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium">↓ Download</a>
            </div>
        </div>

        @if(isset($analysis['error']))
            <div class="bg-red-900/50 border border-red-700 text-red-300 rounded-lg p-4 mb-6">
                {{ $analysis['error'] }}
            </div>
        @else

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Archive Contents --}}
                @if($analysis['is_archive'] && !empty($analysis['archive_contents']))
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">📦 Archive Contents ({{ count($analysis['archive_contents']) }} files)</h2>
                        <div class="bg-gray-900 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-800">
                                    <tr>
                                        <th class="text-left text-gray-400 px-4 py-2 font-medium">File</th>
                                        <th class="text-right text-gray-400 px-4 py-2 font-medium">Size</th>
                                        <th class="text-center text-gray-400 px-4 py-2 font-medium">Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analysis['archive_contents'] as $file)
                                        <tr class="border-t border-gray-800 {{ $file['is_demo'] ? 'bg-amber-900/10' : '' }}">
                                            <td class="px-4 py-2 text-gray-300 font-mono text-xs">
                                                @if($file['is_demo'])<span class="text-amber-400">🎬 </span>@endif
                                                {{ $file['path'] }}
                                            </td>
                                            <td class="px-4 py-2 text-gray-400 text-right text-xs">
                                                {{ number_format($file['size'] / 1048576, 2) }} MB
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                @if($file['is_demo'])
                                                    <span class="bg-amber-600/20 text-amber-400 px-2 py-0.5 rounded text-xs">Demo</span>
                                                @else
                                                    <span class="text-gray-500 text-xs">{{ pathinfo($file['path'], PATHINFO_EXTENSION) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                {{-- Parsed Demo Files --}}
                @foreach($analysis['demo_files'] as $i => $df)
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">
                            🎬 {{ $df['file_name'] ?? 'Demo File' }}
                            @if(isset($df['demo_format']))
                                <span class="bg-orange-900/40 text-orange-400 px-2 py-0.5 rounded text-xs ml-2">{{ $df['demo_format'] }}</span>
                            @endif
                            @if(isset($df['file_size']))
                                <span class="text-gray-500 text-sm font-normal ml-2">{{ number_format($df['file_size'] / 1048576, 2) }} MB</span>
                            @endif
                        </h2>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @if($df['server_name'] ?? null)
                                <div class="bg-gray-900 rounded-lg p-3">
                                    <div class="text-gray-500 text-xs mb-1">Server</div>
                                    <div class="text-white text-sm font-medium">{{ Str::limit($df['server_name'], 30) }}</div>
                                </div>
                            @endif
                            @if($df['map_name'] ?? null)
                                <div class="bg-gray-900 rounded-lg p-3">
                                    <div class="text-gray-500 text-xs mb-1">Map</div>
                                    <div class="text-amber-400 text-sm font-medium">{{ $df['map_name'] }}</div>
                                </div>
                            @endif
                            @if($df['mod_name'] ?? null)
                                <div class="bg-gray-900 rounded-lg p-3">
                                    <div class="text-gray-500 text-xs mb-1">Mod</div>
                                    <div class="text-purple-400 text-sm font-medium">{{ $df['mod_name'] }}</div>
                                </div>
                            @endif
                            @if($df['gametype'] ?? null)
                                <div class="bg-gray-900 rounded-lg p-3">
                                    <div class="text-gray-500 text-xs mb-1">Gametype</div>
                                    <div class="text-white text-sm font-medium">{{ $df['gametype'] }}</div>
                                </div>
                            @endif
                            @if($df['duration_seconds'] ?? null)
                                <div class="bg-gray-900 rounded-lg p-3">
                                    <div class="text-gray-500 text-xs mb-1">Duration (est.)</div>
                                    <div class="text-white text-sm font-medium">~{{ gmdate('H:i:s', $df['duration_seconds']) }}</div>
                                </div>
                            @endif
                        </div>

                        {{-- Players --}}
                        @if(!empty($df['player_list']))
                            <div class="mt-4 pt-4 border-t border-gray-700">
                                <h3 class="text-white font-semibold text-sm mb-3">👥 Players ({{ count($df['player_list']) }})</h3>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <div class="text-red-400 text-xs font-semibold uppercase tracking-wider mb-2">Axis</div>
                                        @foreach(collect($df['player_list'])->where('team', 'axis') as $p)
                                            <div class="bg-red-900/20 border border-red-900/30 rounded px-3 py-1.5 text-sm text-gray-200 mb-1">{{ $p['name'] }}</div>
                                        @endforeach
                                    </div>
                                    <div>
                                        <div class="text-blue-400 text-xs font-semibold uppercase tracking-wider mb-2">Allies</div>
                                        @foreach(collect($df['player_list'])->where('team', 'allies') as $p)
                                            <div class="bg-blue-900/20 border border-blue-900/30 rounded px-3 py-1.5 text-sm text-gray-200 mb-1">{{ $p['name'] }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                @php $specs = collect($df['player_list'])->where('team', 'spectator'); @endphp
                                @if($specs->isNotEmpty())
                                    <div class="mt-2 text-gray-500 text-xs">Spectators: {{ $specs->pluck('name')->implode(', ') }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Chat Log --}}
                @if(!empty($analysis['parsed_data']['chat_log']))
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                        <h2 class="text-lg font-semibold text-white mb-4">💬 Chat Log ({{ count($analysis['parsed_data']['chat_log']) }} messages)</h2>
                        <div class="bg-gray-900 rounded-lg p-4 max-h-96 overflow-y-auto font-mono text-xs space-y-1">
                            @foreach($analysis['parsed_data']['chat_log'] as $msg)
                                <div>
                                    <span class="text-amber-400">{{ $msg['player'] }}</span><span class="text-gray-600">:</span>
                                    <span class="text-gray-300">{{ $msg['message'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Hex Preview --}}
                @if(!empty($analysis['hex_preview']))
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                            <h2 class="text-lg font-semibold text-white">🔍 Binary Hex Preview</h2>
                            <span class="text-gray-400 text-sm" x-text="open ? '▲ Hide' : '▼ Show'"></span>
                        </button>
                        <div x-show="open" x-cloak class="mt-4">
                            <pre class="bg-gray-900 rounded-lg p-4 overflow-x-auto font-mono text-xs text-green-400 leading-relaxed max-h-80 overflow-y-auto">{{ $analysis['hex_preview'] }}</pre>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- File Info --}}
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-white font-semibold mb-4">File Info</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Filename</dt>
                            <dd class="text-white text-xs text-right max-w-[160px] truncate" title="{{ $demo->file_name }}">{{ $demo->file_name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Size</dt>
                            <dd class="text-white">{{ $demo->file_size_formatted }}</dd>
                        </div>
                        @if($demo->file_hash)
                            <div>
                                <dt class="text-gray-400 mb-1">SHA-256</dt>
                                <dd class="text-green-400 font-mono text-xs break-all bg-gray-900 rounded p-2">{{ $demo->file_hash }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Archive</dt>
                            <dd class="text-white">{{ $analysis['is_archive'] ? '✅ Yes' : '❌ No' }}</dd>
                        </div>
                        @if($analysis['is_archive'])
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Demo files</dt>
                                <dd class="text-amber-400 font-medium">{{ count($analysis['demo_files']) }}</dd>
                            </div>
                        @endif
                        @if($demo->demo_format)
                            <div class="flex justify-between">
                                <dt class="text-gray-400">Format</dt>
                                <dd><span class="bg-orange-900/40 text-orange-400 px-2 py-0.5 rounded text-xs">{{ $demo->format_badge }}</span></dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Config Strings --}}
                @if(!empty($analysis['config_strings']))
                    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6" x-data="{ showAll: false }">
                        <h3 class="text-white font-semibold mb-4">⚙️ Server Config ({{ count($analysis['config_strings']) }})</h3>
                        <div class="space-y-1 max-h-96 overflow-y-auto">
                            @foreach($analysis['config_strings'] as $key => $value)
                                <div class="@if(!$loop->iteration <= 20 && !isset($showAll)) hidden @endif"
                                     x-show="$loop->iteration <= 20 || showAll"
                                     x-cloak>
                                    <span class="text-cyan-400 font-mono text-xs">{{ $key }}</span>
                                    <span class="text-gray-600 text-xs">=</span>
                                    <span class="text-gray-300 font-mono text-xs break-all">{{ Str::limit($value, 60) }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if(count($analysis['config_strings']) > 20)
                            <button @click="showAll = !showAll" class="mt-3 text-amber-400 hover:text-amber-300 text-xs">
                                <span x-text="showAll ? 'Show less' : 'Show all {{ count($analysis['config_strings']) }} entries'"></span>
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Quick Actions --}}
                <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
                    <h3 class="text-white font-semibold mb-4">Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('demos.download', $demo) }}" class="block w-full bg-amber-600 hover:bg-amber-700 text-white text-center py-2.5 rounded-lg font-medium text-sm transition-colors">
                            ↓ Download Demo
                        </a>
                        <a href="{{ route('demos.show', $demo) }}" class="block w-full bg-gray-700 hover:bg-gray-600 text-gray-300 text-center py-2.5 rounded-lg text-sm transition-colors">
                            ← Demo Details
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @endif
    </div>
</x-layouts.app>
