<x-filament-panels::page>
    @php
        $data = $this->getAllData();
        $totals = $data['totals'];
        $chart = $data['chartData'];
        $topPages = $data['topPages'];
        $referrers = $data['referrers'];
        $countries = $data['countries'];
        $browsers = $data['browsers'];
        $devices = $data['devices'];
        $languages = $data['languages'];
    @endphp

    {{-- Period Selector --}}
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        @php $a = 'px-4 py-2 rounded-lg text-sm font-medium border'; $on = 'border-indigo-500'; $off = 'bg-gray-800 text-gray-300 border-gray-600 hover:bg-gray-700'; @endphp
        <button wire:click="setPeriod('1')" type="button" class="{{ $a }} {{ $period==='1' ? $on : $off }}" style="{{ $period==='1' ? 'background:#6366f1;color:#fff;' : '' }}">Today</button>
        <button wire:click="setPeriod('7')" type="button" class="{{ $a }} {{ $period==='7' ? $on : $off }}" style="{{ $period==='7' ? 'background:#6366f1;color:#fff;' : '' }}">7 Days</button>
        <button wire:click="setPeriod('30')" type="button" class="{{ $a }} {{ $period==='30' ? $on : $off }}" style="{{ $period==='30' ? 'background:#6366f1;color:#fff;' : '' }}">30 Days</button>
        <button wire:click="setPeriod('90')" type="button" class="{{ $a }} {{ $period==='90' ? $on : $off }}" style="{{ $period==='90' ? 'background:#6366f1;color:#fff;' : '' }}">90 Days</button>
        <button wire:click="setPeriod('all')" type="button" class="{{ $a }} {{ $period==='all' ? $on : $off }}" style="{{ $period==='all' ? 'background:#6366f1;color:#fff;' : '' }}">All Time</button>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-green-500">{{ $totals['online'] }}</div>
            <div class="text-xs text-gray-500">Online Now</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold">{{ number_format($totals['today_visitors']) }}</div>
            <div class="text-xs text-gray-500">Today Visitors</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold">{{ number_format($totals['today_pageviews']) }}</div>
            <div class="text-xs text-gray-500">Today Pageviews</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-blue-500">{{ number_format($totals['unique_visitors']) }}</div>
            <div class="text-xs text-gray-500">Period Visitors</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-blue-500">{{ number_format($totals['pageviews']) }}</div>
            <div class="text-xs text-gray-500">Period Pageviews</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-amber-500">{{ number_format($totals['all_time_visitors']) }}</div>
            <div class="text-xs text-gray-500">All-Time Visitors</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="text-2xl font-bold text-amber-500">{{ number_format($totals['all_time_pageviews']) }}</div>
            <div class="text-xs text-gray-500">All-Time Views</div>
        </div>
    </div>

    {{-- World Map --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
        <h3 class="text-lg font-semibold mb-4">🌍 Visitor Map</h3>
        <div id="worldMap" style="height: 420px;"></div>
    </div>

    {{-- Daily Chart --}}
    @if(count($chart['labels']) > 1)
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
        <h3 class="text-lg font-semibold mb-4">📊 Daily Traffic</h3>
        <canvas id="dailyChart" height="80"></canvas>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Top Pages --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">📄 Top Pages</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($topPages as $page)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-300 truncate flex-1 mr-3 font-mono text-xs">{{ $page->path }}</span>
                    <span class="text-gray-500 whitespace-nowrap">{{ number_format($page->views) }} views / {{ number_format($page->unique_views) }} unique</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Referrers --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">🔗 Top Referrers</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($referrers as $ref)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-300 truncate flex-1 mr-3">{{ $ref->domain }}</span>
                    <span class="text-gray-500">{{ number_format($ref->visits) }}</span>
                </div>
                @empty
                <div class="text-gray-500 text-sm">No external referrers yet. Traffic sources like Google, Discord etc. will appear here.</div>
                @endforelse
            </div>

            <h4 class="text-sm font-semibold mt-6 mb-3 text-gray-400">📍 Internal Navigation</h4>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                @php
                    $internal = \Illuminate\Support\Facades\DB::table('site_analytics')
                        ->selectRaw('referrer, COUNT(*) as visits')
                        ->where('created_at', '>=', $this->getStartDate())
                        ->whereNotNull('referrer')
                        ->where('referrer', 'like', '%wolffiles.eu%')
                        ->where('referrer', 'not like', '%/admin%')
                        ->groupBy('referrer')
                        ->orderByDesc('visits')
                        ->limit(10)
                        ->get();
                @endphp
                @forelse($internal as $ref)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-400 truncate flex-1 mr-3 font-mono text-xs">{{ str_replace('https://wolffiles.eu', '', $ref->referrer) }}</span>
                    <span class="text-gray-500">{{ number_format($ref->visits) }}</span>
                </div>
                @empty
                <div class="text-gray-500 text-xs">No data yet</div>
                @endforelse
            </div>
            
            <div class="mt-4 text-xs text-gray-600">
                📊 {{ $total = \Illuminate\Support\Facades\DB::table('site_analytics')->where('created_at', '>=', $this->getStartDate())->count() }} total visits · 
                {{ \Illuminate\Support\Facades\DB::table('site_analytics')->where('created_at', '>=', $this->getStartDate())->whereNull('referrer')->count() }} direct · 
                {{ \Illuminate\Support\Facades\DB::table('site_analytics')->where('created_at', '>=', $this->getStartDate())->where('referrer', 'like', '%wolffiles%')->count() }} internal
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Countries --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">🌍 Countries</h3>
            <div class="space-y-2 max-h-72 overflow-y-auto">
                @forelse($countries as $c)
                <div class="flex justify-between items-center text-sm">
                    <span class="flex items-center gap-1.5"><x-country-flag :code="strtolower(substr($c->country, 0, 2))" size="16" /> {{ $c->country }}</span>
                    <span class="text-gray-500">{{ number_format($c->visitors) }} visitors · {{ number_format($c->views) }} views</span>
                </div>
                @empty
                <div class="text-gray-500 text-sm">No data yet</div>
                @endforelse
            </div>
        </div>

        {{-- Browsers --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">🌐 Browsers</h3>
            <div class="space-y-2 max-h-72 overflow-y-auto">
                @forelse($browsers as $b)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-300">{{ $b->browser }}</span>
                    <span class="text-gray-500">{{ number_format($b->count) }}</span>
                </div>
                @empty
                <div class="text-gray-500 text-sm">No data yet</div>
                @endforelse
            </div>
        </div>

        {{-- Devices --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">📱 Devices</h3>
            <div class="space-y-2">
                @forelse($devices as $d)
                @php
                    $icon = match($d->device) {
                        'Desktop' => '🖥️', 'Mobile' => '📱', 'Tablet' => '📋', 'Bot' => '🤖', default => '❓'
                    };
                    $total = array_sum(array_column($devices, 'count'));
                    $pct = $total > 0 ? round(($d->count / $total) * 100) : 0;
                @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $icon }} {{ $d->device }}</span>
                        <span class="text-gray-500">{{ $pct }}%</span>
                    </div>
                    <div style="width:100%;background:#374151;border-radius:9999px;height:6px;overflow:hidden;">
                        <div style="width:{{ $pct }}%;height:100%;border-radius:9999px;background:#3b82f6;"></div>
                    </div>
                </div>
                @empty
                <div class="text-gray-500 text-sm">No data yet</div>
                @endforelse
            </div>
        </div>

        {{-- Languages --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">🗣️ Languages</h3>
            <div class="space-y-2">
                @php
                    $langConfig = config('languages', []);
                @endphp
                @forelse($languages as $l)
                @php
                    $info = $langConfig[$l->locale] ?? ['flag' => '🏳️', 'name' => strtoupper($l->locale)];
                    $total = array_sum(array_column($languages, 'count'));
                    $pct = $total > 0 ? round(($l->count / $total) * 100) : 0;
                @endphp
                <div class="flex justify-between items-center text-sm">
                    <span class="flex items-center gap-1.5"><img src="https://flagcdn.com/{{ $info['country'] ?? strtolower($l->locale) }}.svg" width="16" class="inline-block rounded-sm" style="width:16px;height:auto" onerror="this.style.display='none'"> {{ $info['name'] }}</span>
                    <span class="text-gray-500">{{ $pct }}% ({{ number_format($l->count) }})</span>
                </div>
                @empty
                <div class="text-gray-500 text-sm">No data yet</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Traffic Sources + UTM --}}
    @php
        $trafficSources = $this->getTrafficSources();
        $utmSources = $this->getUtmSources();
        $utmCampaigns = $this->getUtmCampaigns();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6 mt-6">
        {{-- Traffic Breakdown --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">📊 Traffic Sources</h3>
            @if(count($trafficSources) > 0)
            <div class="space-y-3">
                @foreach($trafficSources as $src)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-300">{{ $src['label'] }}</span>
                        <span class="text-gray-500">{{ $src['pct'] }}% ({{ number_format($src['count']) }})</span>
                    </div>
                    <div style="width:100%;background:#374151;border-radius:9999px;height:8px;overflow:hidden;">
                        <div style="width:{{ $src['pct'] }}%;height:100%;border-radius:9999px;background:{{ $src['color'] }};"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-gray-500 text-sm">No data yet</div>
            @endif
        </div>

        {{-- UTM Sources --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">🎯 UTM Sources</h3>
            @if(count($utmSources) > 0)
            <div class="space-y-2 max-h-72 overflow-y-auto">
                @foreach($utmSources as $u)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-amber-400">{{ $u->utm_source }}</span>
                    <span class="text-gray-500">{{ number_format($u->visitors) }} visitors · {{ number_format($u->visits) }} views</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-gray-500 text-sm">No UTM data yet.</div>
            <div class="text-gray-600 text-xs mt-2">Use links like:<br><code class="text-amber-500">wolffiles.eu?utm_source=reddit&amp;utm_campaign=promo</code></div>
            @endif
        </div>

        {{-- UTM Campaigns --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">📣 Campaigns</h3>
            @if(count($utmCampaigns) > 0)
            <div class="space-y-2 max-h-72 overflow-y-auto">
                @foreach($utmCampaigns as $c)
                <div class="flex justify-between items-center text-sm">
                    <div>
                        <span class="text-green-400">{{ $c->utm_campaign }}</span>
                        <span class="text-gray-600 text-xs ml-1">via {{ $c->utm_source }}</span>
                    </div>
                    <span class="text-gray-500">{{ number_format($c->visitors) }} / {{ number_format($c->visits) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-gray-500 text-sm">No campaigns yet.</div>
            @endif
        </div>
    </div>

    {{-- Heatmap Section --}}
    @php
        $heatmapPages = $this->getHeatmapPages();
        $topClicked = $this->getTopClickedElements();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 mt-6">
        {{-- Heatmap Pages --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">🔥 Click Heatmap — Top Pages</h3>
            @if(count($heatmapPages) > 0)
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($heatmapPages as $hp)
                <div class="flex justify-between items-center text-sm" x-data>
                    <button @click="$dispatch('show-heatmap', { path: '{{ $hp->path }}' })"
                        class="text-blue-400 hover:text-blue-300 truncate flex-1 mr-3 text-left font-mono text-xs">
                        {{ $hp->path }}
                    </button>
                    <span class="text-gray-500 whitespace-nowrap">{{ number_format($hp->clicks) }} clicks</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-gray-500 text-sm">Noch keine Klick-Daten. Daten werden ab jetzt gesammelt.</div>
            @endif
        </div>

        {{-- Top Clicked Elements --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold mb-4">👆 Most Clicked Elements</h3>
            @if(count($topClicked) > 0)
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach($topClicked as $el)
                <div class="flex justify-between items-center text-sm">
                    <div class="flex-1 mr-3">
                        <span class="text-amber-400 font-mono text-xs">{{ $el->element }}</span>
                        <span class="text-gray-600 text-xs ml-2">{{ $el->path }}</span>
                    </div>
                    <span class="text-gray-500 whitespace-nowrap">{{ number_format($el->clicks) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-gray-500 text-sm">Noch keine Klick-Daten.</div>
            @endif
        </div>
    </div>

    {{-- Heatmap Overlay Modal --}}
    <div x-data="{ showHeatmap: false, heatmapPath: '/', heatmapPoints: [] }"
         @show-heatmap.window="
            showHeatmap = true;
            heatmapPath = $event.detail.path;
            fetch('/api/heatmap-data?path=' + encodeURIComponent($event.detail.path) + '&period={{ $period }}')
                .then(r => r.json())
                .then(d => heatmapPoints = d);
         ">
        <div x-show="showHeatmap" x-cloak class="fixed inset-0 z-50 bg-black/80 flex flex-col">
            <div class="flex justify-between items-center p-4 bg-gray-900">
                <h3 class="text-white font-semibold">🔥 Heatmap: <span x-text="heatmapPath" class="text-amber-400 font-mono"></span></h3>
                <button @click="showHeatmap = false" class="text-white bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600">✕ Close</button>
            </div>
            <div class="flex-1 overflow-auto relative bg-gray-900 p-4">
                <div class="relative mx-auto" style="width: 100%; max-width: 1200px; min-height: 2000px;">
                    {{-- Heatmap Canvas --}}
                    <canvas id="heatmapCanvas" width="1200" height="3000" class="w-full" style="background: #1a1a2e;"></canvas>
                    <script>
                        document.addEventListener('alpine:init', () => {
                            Alpine.effect(() => {
                                const el = document.querySelector('[x-data]');
                                if (!el || !el.__x) return;
                            });
                        });

                        function drawHeatmap(points) {
                            const canvas = document.getElementById('heatmapCanvas');
                            if (!canvas) return;
                            const ctx = canvas.getContext('2d');
                            ctx.clearRect(0, 0, canvas.width, canvas.height);

                            // Grid background
                            ctx.strokeStyle = 'rgba(255,255,255,0.05)';
                            for (let y = 0; y < canvas.height; y += 100) {
                                ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(canvas.width, y); ctx.stroke();
                                ctx.fillStyle = 'rgba(255,255,255,0.15)';
                                ctx.font = '10px monospace';
                                ctx.fillText(y + 'px', 5, y + 12);
                            }

                            if (!points || points.length === 0) {
                                ctx.fillStyle = 'rgba(255,255,255,0.3)';
                                ctx.font = '16px sans-serif';
                                ctx.fillText('No click data for this page yet', canvas.width/2 - 120, 200);
                                return;
                            }

                            const maxV = Math.max(...points.map(p => p.v));

                            points.forEach(p => {
                                const x = (p.x / 100) * canvas.width;
                                const y = Math.min(p.y, canvas.height - 10);
                                const intensity = p.v / maxV;
                                const radius = 15 + intensity * 25;

                                const gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
                                if (intensity > 0.7) {
                                    gradient.addColorStop(0, 'rgba(255, 0, 0, 0.8)');
                                    gradient.addColorStop(0.5, 'rgba(255, 100, 0, 0.4)');
                                } else if (intensity > 0.3) {
                                    gradient.addColorStop(0, 'rgba(255, 200, 0, 0.7)');
                                    gradient.addColorStop(0.5, 'rgba(255, 150, 0, 0.3)');
                                } else {
                                    gradient.addColorStop(0, 'rgba(0, 150, 255, 0.6)');
                                    gradient.addColorStop(0.5, 'rgba(0, 100, 255, 0.2)');
                                }
                                gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');

                                ctx.beginPath();
                                ctx.arc(x, y, radius, 0, Math.PI * 2);
                                ctx.fillStyle = gradient;
                                ctx.fill();
                            });
                        }

                        window.addEventListener('show-heatmap', function() {
                            setTimeout(() => {
                                const el = document.querySelector('[x-data]');
                                if (el && el._x_dataStack) {
                                    const data = Alpine.$data(el);
                                    if (data.heatmapPoints) drawHeatmap(data.heatmapPoints);
                                }
                            }, 500);
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Daily Chart
            @if(count($chart['labels']) > 1)
            new Chart(document.getElementById('dailyChart'), {
                type: 'bar',
                data: {
                    labels: @json($chart['labels']),
                    datasets: [
                        {
                            label: 'Pageviews',
                            data: @json($chart['views']),
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1,
                        },
                        {
                            label: 'Visitors',
                            data: @json($chart['visitors']),
                            backgroundColor: 'rgba(245, 158, 11, 0.5)',
                            borderColor: 'rgb(245, 158, 11)',
                            borderWidth: 1,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' } },
                        x: { grid: { display: false } }
                    },
                    plugins: { legend: { labels: { color: '#9ca3af' } } }
                }
            });
            @endif

            // World Map
            const countryData = @json($this->getCountryMapData());
            const values = Object.values(countryData);
            const maxVal = Math.max(...values, 1);

            new jsVectorMap({
                selector: '#worldMap',
                map: 'world',
                backgroundColor: 'transparent',
                zoomButtons: true,
                zoomOnScroll: true,
                regionStyle: {
                    initial: { fill: '#374151', stroke: '#1f2937', strokeWidth: 0.5 },
                    hover: { fill: '#6b7280' },
                },
                series: {
                    regions: [{
                        attribute: 'fill',
                        scale: { low: '#1e3a5f', high: '#f59e0b' },
                        values: countryData,
                        min: 0,
                        max: maxVal,
                    }]
                },
                onRegionTooltipShow(event, tooltip, code) {
                    const count = countryData[code] || 0;
                    tooltip.text(tooltip.text() + ': ' + count + ' visitors');
                }
            });
        });
    </script>
</x-filament-panels::page>
