{{-- 
    Related Map files for a Bot/Waypoint file.
    Usage:  @include('frontend.files.partials._related_maps', ['file' => $file])
    Renders nothing if $file is not a bot file or has no related maps.
--}}
@php
    $relMaps = ($file->isBotFile() ?? false)
        ? $file->relatedMaps()->where('status', 'approved')->get()
        : collect();
@endphp

@if($relMaps->isNotEmpty())
<section class="related-maps mt-8 bg-gray-800/40 rounded-lg p-5 border border-gray-700">
    <h2 class="text-xl font-semibold text-white mb-1 flex items-center gap-2">
        <span>🗺️</span>
        {{ __('messages.related_maps') }}
        <span class="text-sm font-normal text-gray-400">({{ $relMaps->count() }})</span>
    </h2>
    <p class="text-sm text-gray-400 mb-4">{{ __('messages.related_maps_hint') }}</p>

    <ul class="divide-y divide-gray-700/50">
        @foreach($relMaps as $map)
            @php
                $conf = (float) $map->pivot->confidence;
                $isManual = (bool) $map->pivot->is_manual;
            @endphp
            <li class="py-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <a href="{{ route('files.show', $map) }}"
                       class="text-amber-400 hover:text-amber-300 font-medium truncate block">
                        {{ $map->display_title ?? $map->title }}
                    </a>
                    <div class="text-xs text-gray-500 mt-0.5 flex flex-wrap gap-x-3 gap-y-1">
                        <span>{{ $map->file_name }}</span>
                        @if($map->map_name_clean)
                            <span class="text-gray-400">{{ $map->map_name_clean }}.bsp</span>
                        @endif
                        @if($map->file_size)
                            <span>{{ $map->file_size_formatted ?? round($map->file_size / 1024 / 1024, 2) . ' MB' }}</span>
                        @endif
                        <span>↓ {{ number_format($map->download_count) }}</span>
                        @if($isManual)
                            <span class="text-emerald-400" title="{{ __('messages.relation_verified') }}">✓ {{ __('messages.verified') }}</span>
                        @elseif($conf < 0.70)
                            <span class="text-yellow-500" title="{{ __('messages.relation_low_confidence') }}">~ {{ __('messages.likely_match') }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('files.download', $map) }}"
                   class="shrink-0 bg-amber-600 hover:bg-amber-700 text-white text-sm px-3 py-1.5 rounded transition-colors"
                   title="{{ __('messages.download') }}">
                    ↓
                </a>
            </li>
        @endforeach
    </ul>
</section>
@endif
