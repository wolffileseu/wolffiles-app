<x-layouts.app title="Statistics">
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-15px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes glowPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); } 50% { box-shadow: 0 0 20px 2px rgba(245,158,11,0.12); } }
        .stat-card { animation: fadeInUp .6s ease-out backwards; }
        .stat-card:nth-child(1){animation-delay:0s}.stat-card:nth-child(2){animation-delay:.08s}
        .stat-card:nth-child(3){animation-delay:.16s}.stat-card:nth-child(4){animation-delay:.24s}
        .stat-card:nth-child(5){animation-delay:.32s}.stat-card:nth-child(6){animation-delay:.4s}
        .stat-card:hover { animation: glowPulse 2s ease-in-out infinite; }
        .list-item { animation: slideIn .4s ease-out backwards; }
        .section-fade { animation: fadeInUp .6s ease-out backwards; }
        .medal-1 { background: linear-gradient(135deg,#fbbf24,#f59e0b); color:#000; }
        .medal-2 { background: linear-gradient(135deg,#d1d5db,#9ca3af); color:#000; }
        .medal-3 { background: linear-gradient(135deg,#d97706,#92400e); color:#fff; }
        .bar-fill { transition: width 1.2s cubic-bezier(.4,0,.2,1); }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <nav class="text-sm text-gray-400 mb-6">
            <a href="{{ route('home') }}" class="hover:text-amber-400 transition-colors">{{ __('messages.home') }}</a> /
            <span class="text-gray-300">{{ __('messages.statistics') }}</span>
        </nav>

        <div class="flex items-center space-x-3 mb-10">
            <div class="w-10 h-10 bg-amber-600/20 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <h1 class="text-3xl font-bold text-white">{{ __('messages.statistics') }}</h1>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-12">
            @php
                $cards = [
                    ['val' => $totalFiles, 'label' => __('messages.files'), 'color' => 'amber', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    ['val' => $totalDownloads, 'label' => __('messages.downloads'), 'color' => 'green', 'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4'],
                    ['val' => $totalMaps, 'label' => 'Maps', 'color' => 'blue', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                    ['val' => $totalUsers, 'label' => __('messages.users'), 'color' => 'purple', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                    ['val' => $totalComments ?? 0, 'label' => __('messages.comments'), 'color' => 'pink', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                    ['val' => $totalRatings ?? 0, 'label' => __('messages.ratings'), 'color' => 'yellow', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                ];
            @endphp
            @foreach($cards as $card)
                <div class="stat-card bg-gray-800/80 backdrop-blur rounded-2xl border border-gray-700/50 p-6 text-center hover:border-{{ $card['color'] }}-500/50 transition-all duration-300">
                    <div class="w-12 h-12 bg-{{ $card['color'] }}-500/10 rounded-xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-{{ $card['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
                    </div>
                    <div class="text-3xl font-bold text-{{ $card['color'] }}-400 tabular-nums"
                         x-data="{v:0,target:{{ $card['val'] }}}"
                         x-init="if(target<100){let t=setInterval(()=>{v++;if(v>=target){v=target;clearInterval(t)}},30)}else{let t=setInterval(()=>{v+=Math.ceil(target/50);if(v>=target){v=target;clearInterval(t)}},25)}">
                        <span x-text="v.toLocaleString()">0</span>
                    </div>
                    <div class="text-gray-500 text-xs font-medium uppercase tracking-wider mt-1">{{ $card['label'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-12">
            <div class="lg:col-span-2 section-fade bg-gray-800/80 backdrop-blur rounded-2xl border border-gray-700/50 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-white flex items-center space-x-2">
                        <div class="w-8 h-8 bg-green-500/10 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <span>Activity ({{ __('messages.last_30_days') }})</span>
                    </h2>
                    <span class="text-xs text-gray-500 bg-gray-700/50 px-3 py-1 rounded-full">Live Data</span>
                </div>
                <div style="position:relative;height:250px"><canvas id="downloadsChart"></canvas></div>
            </div>
            <div class="section-fade bg-gray-800/80 backdrop-blur rounded-2xl border border-gray-700/50 p-6">
                <div class="flex items-center space-x-2 mb-5">
                    <div class="w-8 h-8 bg-amber-500/10 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
                    </div>
                    <h2 class="text-lg font-semibold text-white">Games</h2>
                </div>
                <div class="flex justify-center mb-5"><canvas id="gamesChart" width="200" height="200"></canvas></div>
                @php $maxG = ($gamesDistribution ?? collect())->max('total') ?: 1; $barColors = ['bg-amber-500','bg-green-500','bg-blue-500','bg-purple-500','bg-red-500','bg-pink-500','bg-teal-500','bg-orange-500']; @endphp
                <div class="space-y-2.5">
                    @foreach($gamesDistribution ?? collect() as $i => $game)
                        <div class="list-item" style="animation-delay:{{ $i * .05 }}s">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-300 text-sm">{{ $game->game ?? 'Unknown' }}</span>
                                <span class="text-gray-500 text-xs tabular-nums">{{ number_format($game->total) }}</span>
                            </div>
                            <div class="w-full bg-gray-700/40 rounded-full h-1.5"><div class="bar-fill {{ $barColors[$i % count($barColors)] }} h-1.5 rounded-full" style="width:{{ round(($game->total / $maxG) * 100) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Top Downloads & Top Rated --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
            @foreach([['title' => 'Top Downloads', 'items' => $topDownloaded, 'field' => 'download_count', 'color' => 'amber', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'format' => 'number'], ['title' => 'Top Rated', 'items' => $topRated, 'field' => 'average_rating', 'color' => 'yellow', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'format' => 'rating']] as $section)
                <div class="section-fade bg-gray-800/80 backdrop-blur rounded-2xl border border-gray-700/50 p-6">
                    <div class="flex items-center space-x-2 mb-6">
                        <div class="w-8 h-8 bg-{{ $section['color'] }}-500/10 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-{{ $section['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"/></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-white">{{ $section['title'] }}</h2>
                    </div>
                    @if($section['items']->isEmpty())
                        <p class="text-gray-600 text-sm text-center py-8">Noch keine Daten vorhanden.</p>
                    @else
                        <div class="space-y-0.5">
                            @foreach($section['items'] as $i => $file)
                                <a href="{{ route('files.show', $file) }}" class="list-item flex items-center space-x-3 py-2.5 px-3 rounded-xl hover:bg-gray-700/40 transition-all duration-200 group" style="animation-delay:{{ $i * .04 }}s">
                                    <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0 {{ $i === 0 ? 'medal-1' : ($i === 1 ? 'medal-2' : ($i === 2 ? 'medal-3' : 'bg-gray-700/80 text-gray-500')) }}">{{ $i + 1 }}</span>
                                    @if($file->thumbnail_url)
                                        <img src="{{ $file->thumbnail_url }}" class="w-12 h-8 object-cover rounded-md flex-shrink-0 border border-gray-700" loading="lazy">
                                    @else
                                        <div class="w-12 h-8 bg-gray-700/60 rounded-md flex-shrink-0 flex items-center justify-center"><svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                                    @endif
                                    <span class="flex-1 text-sm text-gray-300 group-hover:text-amber-400 truncate transition-colors">{{ $file->title }}</span>
                                    @if($section['format'] === 'rating')
                                        <div class="flex items-center space-x-1 flex-shrink-0">
                                            @for($s = 1; $s <= 5; $s++)
                                                <svg class="w-3 h-3 {{ $s <= round($file->average_rating) ? 'text-amber-400' : 'text-gray-700' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                            @endfor
                                            <span class="text-gray-500 text-xs ml-0.5">({{ $file->rating_count }})</span>
                                        </div>
                                    @else
                                        <span class="text-amber-400 font-semibold text-sm flex-shrink-0 tabular-nums">{{ number_format($file->{$section['field']}) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        {{-- Most Viewed & Top Uploaders --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
            @foreach([['title' => 'Most Viewed', 'items' => $topViewed, 'field' => 'view_count', 'color' => 'blue', 'suffix' => ' views', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z'], ['title' => 'Top Uploaders', 'items' => $topUploaders, 'field' => 'approved_uploads_count', 'color' => 'purple', 'suffix' => ' files', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z']] as $section)
                <div class="section-fade bg-gray-800/80 backdrop-blur rounded-2xl border border-gray-700/50 p-6">
                    <div class="flex items-center space-x-2 mb-6">
                        <div class="w-8 h-8 bg-{{ $section['color'] }}-500/10 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-{{ $section['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"/></svg>
                        </div>
                        <h2 class="text-lg font-semibold text-white">{{ $section['title'] }}</h2>
                    </div>
                    @if($section['items']->isEmpty())
                        <p class="text-gray-600 text-sm text-center py-8">Noch keine Daten.</p>
                    @else
                        @php $maxVal = $section['items']->first()->{$section['field']} ?: 1; @endphp
                        <div class="space-y-0.5">
                            @foreach($section['items'] as $i => $item)
                                @php $isUploader = ($section['field'] === 'approved_uploads_count'); @endphp
                                <a href="{{ $isUploader ? route('profile.show', $item) : route('files.show', $item) }}" class="list-item flex items-center space-x-3 py-2.5 px-3 rounded-xl hover:bg-gray-700/40 transition-all duration-200 group" style="animation-delay:{{ $i * .04 }}s">
                                    <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0 {{ $i === 0 ? 'medal-1' : ($i === 1 ? 'medal-2' : ($i === 2 ? 'medal-3' : 'bg-gray-700/80 text-gray-500')) }}">{{ $i + 1 }}</span>
                                    @if($isUploader)
                                        <div class="w-8 h-8 bg-{{ $section['color'] }}-500/20 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-bold text-{{ $section['color'] }}-400">{{ strtoupper(substr($item->name, 0, 1)) }}</div>
                                    @elseif($item->thumbnail_url ?? false)
                                        <img src="{{ $item->thumbnail_url }}" class="w-12 h-8 object-cover rounded-md flex-shrink-0 border border-gray-700" loading="lazy">
                                    @else
                                        <div class="w-12 h-8 bg-gray-700/60 rounded-md flex-shrink-0 flex items-center justify-center"><svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm text-gray-300 group-hover:text-amber-400 truncate transition-colors">{{ $isUploader ? $item->name : $item->title }}</div>
                                        <div class="w-full bg-gray-700/30 rounded-full h-1 mt-1"><div class="bar-fill bg-{{ $section['color'] }}-500/50 h-1 rounded-full" style="width:{{ round(($item->{$section['field']} / $maxVal) * 100) }}%"></div></div>
                                    </div>
                                    <span class="text-{{ $section['color'] }}-400 font-semibold text-sm flex-shrink-0 tabular-nums">{{ number_format($item->{$section['field']}) }}{{ $section['suffix'] ?? '' }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Categories grouped like /categories page --}}
        <div class="section-fade space-y-6 mb-8">
            <div class="flex items-center space-x-2 mb-2">
                <div class="w-8 h-8 bg-amber-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </div>
                <h2 class="text-lg font-semibold text-white">Files per Category</h2>
            </div>

            @foreach($categoryGroups as $group)
                @if($group->total_files > 0)
                    <div class="bg-gray-800/80 backdrop-blur rounded-2xl border border-gray-700/50 overflow-hidden">
                        <div class="flex items-center justify-between p-5">
                            <div class="flex items-center space-x-3">
                                @if($group->icon)
                                    <span class="text-2xl">{{ $group->icon }}</span>
                                @else
                                    <div class="w-9 h-9 bg-amber-600/20 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                    </div>
                                @endif
                                <h3 class="text-lg font-semibold text-white">{{ $group->name_translations[app()->getLocale()] ?? $group->name }}</h3>
                            </div>
                            <span class="bg-gray-700/60 text-amber-400 px-4 py-1.5 rounded-full text-sm font-semibold tabular-nums">
                                {{ number_format($group->total_files) }} {{ __('messages.files') }}
                            </span>
                        </div>
                        @if($group->children->isNotEmpty())
                            <div class="border-t border-gray-700/50 px-5 py-4 bg-gray-900/20">
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                                    @foreach($group->children->sortByDesc('approved_files_count') as $child)
                                        <a href="{{ route('categories.show', $child) }}"
                                           class="flex items-center justify-between bg-gray-700/30 hover:bg-gray-700/60 rounded-lg px-4 py-2.5 gap-3 transition-all duration-200 group">
                                            <span class="text-sm text-gray-400 group-hover:text-amber-400 truncate transition-colors pr-2">{{ $child->name_translations[app()->getLocale()] ?? $child->name }}</span>
                                            <span class="text-xs text-amber-400 font-semibold ml-auto flex-shrink-0 tabular-nums">{{ number_format($child->approved_files_count) }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const ttip = { backgroundColor:'#1f2937', titleColor:'#f3f4f6', bodyColor:'#d1d5db', borderColor:'#374151', borderWidth:1, padding:12, cornerRadius:8 };

        // Activity Chart
        const dlCtx = document.getElementById('downloadsChart');
        if (dlCtx) {
            new Chart(dlCtx, {
                type: 'bar',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                        label: 'Downloads',
                        data: @json($chartData),
                        backgroundColor: 'rgba(16,185,129,0.6)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6,
                    }, {
                        label: 'Uploads',
                        data: @json($uploadChartData ?? []),
                        backgroundColor: 'rgba(245,158,11,0.6)',
                        borderColor: '#f59e0b',
                        borderWidth: 1,
                        borderRadius: 4,
                        barPercentage: 0.6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    animation: { duration: 1200, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { labels: { color: '#9ca3af', usePointStyle: true, pointStyle: 'rectRounded', padding: 16 } },
                        tooltip: ttip
                    },
                    scales: {
                        x: { ticks: { color: '#6b7280', maxTicksLimit: 10, font: { size: 11 } }, grid: { display: false } },
                        y: { ticks: { color: '#6b7280', font: { size: 11 }, precision: 0 }, grid: { color: 'rgba(55,65,81,0.3)' }, beginAtZero: true }
                    }
                }
            });
        }

        // Games Donut
        const gd = @json($gamesDistribution ?? []);
        const gc = ['#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#ec4899','#14b8a6','#f97316'];
        const gCtx = document.getElementById('gamesChart');
        if (gd.length > 0 && gCtx) {
            new Chart(gCtx, {
                type: 'doughnut',
                data: {
                    labels: gd.map(g => g.game || 'Unknown'),
                    datasets: [{ data: gd.map(g => g.total), backgroundColor: gc.slice(0, gd.length), borderColor: '#111827', borderWidth: 3 }]
                },
                options: {
                    responsive: false, cutout: '65%',
                    animation: { animateRotate: true, duration: 1500, easing: 'easeOutQuart' },
                    plugins: { legend: { display: false }, tooltip: ttip }
                }
            });
        }
    </script>
    @endpush
</x-layouts.app>
