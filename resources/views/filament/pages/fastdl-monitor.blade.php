<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $topFiles = $this->getTopFiles();
        $dailyStats = $this->getDailyStats();
        $gameStats = $this->getGameStats();
        $recentDls = $this->getRecentDownloads();

        function humanSize($bytes) {
            if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
            if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
            if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
            return $bytes . ' B';
        }
    @endphp

    {{-- Overview Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-primary-500">{{ number_format($stats['total_files']) }}</div>
            <div class="text-sm text-gray-500">Total Files</div>
            <div class="text-xs text-gray-400">{{ humanSize($stats['total_size']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-success-500">{{ number_format($stats['today']) }}</div>
            <div class="text-sm text-gray-500">Downloads Today</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-warning-500">{{ number_format($stats['week']) }}</div>
            <div class="text-sm text-gray-500">This Week</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="text-2xl font-bold text-info-500">{{ number_format($stats['month']) }}</div>
            <div class="text-sm text-gray-500">This Month</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Games Overview --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
            <h3 class="text-lg font-semibold mb-4">📊 Games Overview</h3>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b dark:border-gray-700">
                        <th class="text-left py-2">Game</th>
                        <th class="text-right py-2">Files</th>
                        <th class="text-right py-2">Size</th>
                        <th class="text-right py-2">Downloads</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gameStats as $game)
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-2 font-medium">{{ $game->name }}</td>
                        <td class="py-2 text-right">{{ number_format($game->file_count) }}</td>
                        <td class="py-2 text-right">{{ humanSize($game->total_size) }}</td>
                        <td class="py-2 text-right">{{ number_format($game->total_dls) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Daily Downloads Chart (last 30 days) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
            <h3 class="text-lg font-semibold mb-4">📈 Daily Downloads (30 days)</h3>
            @if($dailyStats->count() > 0)
            <div class="space-y-1">
                @php $maxCount = $dailyStats->max('count') ?: 1; @endphp
                @foreach($dailyStats->take(14) as $day)
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-16 text-gray-500">{{ \Carbon\Carbon::parse($day->date)->format('d.m') }}</span>
                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                        <div class="h-full bg-primary-500 rounded-full" style="width: {{ ($day->count / $maxCount) * 100 }}%"></div>
                    </div>
                    <span class="w-8 text-right text-gray-500">{{ $day->count }}</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-sm">No downloads yet.</p>
            @endif
        </div>

        {{-- Top Downloaded Files --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
            <h3 class="text-lg font-semibold mb-4">🏆 Top Downloaded Files</h3>
            @if($topFiles->count() > 0)
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-500 border-b dark:border-gray-700">
                        <th class="text-left py-2">#</th>
                        <th class="text-left py-2">File</th>
                        <th class="text-right py-2">Downloads</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topFiles as $i => $file)
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-1">{{ $i + 1 }}</td>
                        <td class="py-1 font-mono text-xs">{{ $file->filename }}</td>
                        <td class="py-1 text-right font-medium">{{ number_format($file->download_count) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-gray-500 text-sm">No downloads yet.</p>
            @endif
        </div>

        {{-- Recent Downloads --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
            <h3 class="text-lg font-semibold mb-4">🕐 Recent Downloads</h3>
            @if($recentDls->count() > 0)
            <div class="space-y-2 text-sm max-h-80 overflow-y-auto">
                @foreach($recentDls as $dl)
                <div class="flex justify-between items-center border-b dark:border-gray-700 pb-1">
                    <span class="font-mono text-xs truncate max-w-[200px]">{{ $dl->path }}</span>
                    <span class="text-gray-500 text-xs">{{ \Carbon\Carbon::parse($dl->created_at)->diffForHumans() }}</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-sm">No downloads yet.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
