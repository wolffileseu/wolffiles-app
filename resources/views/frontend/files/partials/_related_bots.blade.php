{{-- 
    Related Bot/Waypoint files for a Map.
    Usage:  @include('frontend.files.partials._related_bots', ['file' => $file])
    Renders nothing if $file is not a map or has no related bots.
--}}
@php
    $relBots = ($file->isMap() ?? false)
        ? $file->relatedBots()->where('status', 'approved')->get()
        : collect();
@endphp

@if($relBots->isNotEmpty())
<section class="related-bots mt-8 bg-gray-800/40 rounded-lg p-5 border border-gray-700">
    <h2 class="text-xl font-semibold text-white mb-1 flex items-center gap-2">
        <span>🤖</span>
        {{ __('messages.related_bot_files') }}
        <span class="text-sm font-normal text-gray-400">({{ $relBots->count() }})</span>
    </h2>
    <p class="text-sm text-gray-400 mb-4">{{ __('messages.related_bot_files_hint') }}</p>

    <ul class="divide-y divide-gray-700/50">
        @foreach($relBots as $bot)
            @php
                $conf = (float) $bot->pivot->confidence;
                $isManual = (bool) $bot->pivot->is_manual;
            @endphp
            <li class="py-3 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <a href="{{ route('files.show', $bot) }}"
                       class="text-amber-400 hover:text-amber-300 font-medium truncate block">
                        {{ $bot->display_title ?? $bot->title }}
                    </a>
                    <div class="text-xs text-gray-500 mt-0.5 flex flex-wrap gap-x-3 gap-y-1">
                        <span>{{ $bot->file_name }}</span>
                        @if($bot->file_size)
                            <span>{{ $bot->file_size_formatted ?? round($bot->file_size / 1024 / 1024, 2) . ' MB' }}</span>
                        @endif
                        <span>↓ {{ number_format($bot->download_count) }}</span>
                        @if($isManual)
                            <span class="text-emerald-400" title="{{ __('messages.relation_verified') }}">✓ {{ __('messages.verified') }}</span>
                        @elseif($conf < 0.70)
                            <span class="text-yellow-500" title="{{ __('messages.relation_low_confidence') }}">~ {{ __('messages.likely_match') }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('files.download', $bot) }}"
                   class="shrink-0 bg-amber-600 hover:bg-amber-700 text-white text-sm px-3 py-1.5 rounded transition-colors"
                   title="{{ __('messages.download') }}">
                    ↓
                </a>
            </li>
        @endforeach
    </ul>
</section>
@endif
