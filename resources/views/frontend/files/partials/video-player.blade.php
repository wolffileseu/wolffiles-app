{{--
  Video Player Partial
  Expects: $file (App\Models\File)
  Renders one of:
    - Plyr player (if playable_status === 'ready')
    - Processing banner (if pending/processing)
    - Failed banner (if failed)
    - Skipped banner (if skipped — usually format-related, fall back to download)
--}}

@php
    $videoCategory = $file->category && (
        $file->category->id === 6 ||
        $file->category->parent_id === 6
    );
    $shouldShowVideoArea = $file->hasVideoContent() || $videoCategory;
@endphp

@if($shouldShowVideoArea)
    @if($file->isPlayableVideo())
        @php
            $posterUrl = $file->primaryScreenshot?->url ?? null;
            $streamUrl = route('files.stream', $file->slug);
        @endphp

        <div class="mb-6" x-data="videoPlayer()" x-init="init($refs.video)" x-cloak>
            <video
                x-ref="video"
                playsinline
                controls
                preload="metadata"
                @if($posterUrl) poster="{{ $posterUrl }}" data-poster="{{ $posterUrl }}" @endif
            >
                <source src="{{ $streamUrl }}" type="{{ $file->playable_mime ?? 'video/mp4' }}">
                {{ __('Your browser does not support HTML5 video.') }}
            </video>

            @if($file->playable_duration_seconds)
                <div class="mt-2 text-xs text-gray-500 flex items-center gap-3">
                    <span>🎬 {{ gmdate($file->playable_duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $file->playable_duration_seconds) }}</span>
                    @if($file->playable_size)
                        <span>{{ round($file->playable_size / 1048576, 1) }} MB streamable</span>
                    @endif
                </div>
            @endif
        </div>

    @elseif(in_array($file->playable_status, ['pending', 'processing']))
        <div class="video-status-banner processing">
            <div class="flex items-center justify-center gap-3">
                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span class="font-medium">{{ __('Video is being processed for playback…') }}</span>
            </div>
            <p class="text-sm mt-2 opacity-80">{{ __('This usually takes a few minutes. Refresh the page later to watch it in your browser.') }}</p>
        </div>

    @elseif($file->playable_status === 'failed')
        <div class="video-status-banner failed">
            <p class="font-medium">{{ __('Video preview unavailable') }}</p>
            <p class="text-sm mt-1 opacity-80">{{ __('The video could not be processed. You can still download the file below.') }}</p>
        </div>

    @elseif($file->playable_status === 'skipped')
        <div class="video-status-banner pending">
            <p class="font-medium">{{ __('In-browser playback not supported') }}</p>
            <p class="text-sm mt-1 opacity-80">{{ __('This video format cannot be streamed directly. Please use the download below.') }}</p>
        </div>

    @endif
@endif
