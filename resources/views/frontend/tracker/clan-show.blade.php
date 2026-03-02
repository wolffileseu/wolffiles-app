<x-layouts.app :title="'[' . $clan->tag_clean . '] ' . ($clan->name ?? '')">
<div class="max-w-7xl mx-auto px-4 py-8">
    <a href="{{ route('tracker.clans') }}" class="text-amber-400 hover:text-amber-300 text-sm">&larr; Back to Clans</a>

    <div class="bg-gray-800 rounded-lg p-6 mt-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-gray-700 rounded-xl flex items-center justify-center text-amber-400 font-bold text-xl border-2 border-amber-500/30">{{ strtoupper(substr($clan->tag_clean, 0, 4)) }}</div>
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-white"><span class="text-amber-500">[{{ $clan->tag_clean }}]</span> {{ $clan->name ?? 'Unknown Clan' }}</h1>
                    @auth
                    @if(!$clan->claimed_by_user_id)
                    <a href="{{ route('tracker.claim.clan', $clan) }}" class="px-3 py-1.5 bg-gray-700 text-amber-400 rounded-lg text-xs hover:bg-gray-600 transition border border-amber-500/30">&#x1f3f0; Claim Clan</a>
                    @elseif($clan->claimed_by_user_id === auth()->id())
                    <span class="px-3 py-1.5 bg-green-900/30 text-green-400 rounded-lg text-xs border border-green-500/30">&#x2713; Your Clan</span>
                    @else
                    <span class="px-3 py-1.5 bg-gray-700 text-gray-500 rounded-lg text-xs">&#x2713; Claimed</span>
                    @endif
                    @endauth
                </div>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-400">
                    @if($clan->country_code)
                    <span class="flex items-center gap-1">
                        <img src="https://flagcdn.com/{{ strtolower($clan->country_code) }}.svg" class="w-4 h-3 rounded-sm" alt="">
                        {{ $clan->country ?? strtoupper($clan->country_code) }}
                    </span>
                    @endif
                    @if($clan->first_seen_at)<span>Since {{ $clan->first_seen_at->format('M Y') }}</span>@endif
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div class="text-center"><div class="text-2xl font-bold text-amber-400">{{ number_format($clan->avg_elo) }}</div><div class="text-gray-500 text-xs">Avg ELO</div></div>
            <div class="text-center"><div class="text-2xl font-bold text-white">{{ $clan->member_count }}</div><div class="text-gray-500 text-xs">Members</div></div>
            <div class="text-center"><div class="text-2xl font-bold text-green-400">{{ $clan->active_member_count ?? 0 }}</div><div class="text-gray-500 text-xs">Active</div></div>
            <div class="text-center"><div class="text-2xl font-bold text-blue-400">{{ round(($clan->total_play_time_minutes ?? 0) / 60, 1) }}h</div><div class="text-gray-500 text-xs">Total Playtime</div></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-900/50 border-b border-gray-700"><h2 class="text-white font-semibold">Members</h2></div>
            <table class="w-full text-sm">
                <thead class="text-gray-400 text-left bg-gray-900/30">
                    <tr><th class="px-4 py-2">#</th><th class="px-4 py-2">Player</th><th class="px-4 py-2 text-center">ELO</th><th class="px-4 py-2">Last Seen</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    @foreach($topPlayers as $i => $player)
                    <tr class="hover:bg-gray-750 transition">
                        <td class="px-4 py-2 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('tracker.player.show', $player) }}" class="text-amber-400 hover:text-amber-300">
                                {!! $player->name_html ?? e($player->name_clean ?? 'Unknown') !!}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-center text-white font-medium">{{ number_format($player->elo_rating) }}</td>
                        <td class="px-4 py-2 text-gray-400 text-xs">{{ $player->last_seen_at?->diffForHumans() ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-gray-800 rounded-lg p-4">
            <h3 class="text-white font-semibold mb-3">Weekly Activity</h3>
            @if($recentActivity->count())
            <div class="h-32"><canvas id="clan-activity-chart"></canvas></div>
            @else
            <p class="text-gray-500 text-sm">No recent activity data</p>
            @endif

            @if($clan->description)
            <div class="mt-6 pt-4 border-t border-gray-700">
                <h3 class="text-white font-semibold mb-2">About</h3>
                <p class="text-gray-400 text-sm">{{ $clan->description }}</p>
            </div>
            @endif

            @if($clan->website)
            <div class="mt-4">
                <a href="{{ $clan->website }}" target="_blank" rel="noopener" class="text-amber-400 hover:text-amber-300 text-sm">{{ $clan->website }}</a>
            </div>
            @endif
        </div>
    </div>
</div>

@if($recentActivity->count())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var labels = @json($recentActivity->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('D')));
    var data = @json($recentActivity->pluck('sessions'));
    new Chart(document.getElementById('clan-activity-chart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sessions',
                data: data,
                backgroundColor: 'rgba(245,158,11,0.25)',
                borderColor: '#f59e0b',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#374151' }, ticks: { color: '#9CA3AF' } },
                x: { grid: { display: false }, ticks: { color: '#9CA3AF' } }
            }
        }
    });
});
</script>
@endif
</x-layouts.app>
