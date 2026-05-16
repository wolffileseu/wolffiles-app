@php
    // Auto-Smart routing: decide best representation
    $isVideo  = $file->isPlayableVideo();
    $isMap    = !empty($file->bsp_path);
    $mode     = $isVideo ? 'player' : 'card';

    $typeBadge = match (true) {
        $isVideo => ['icon' => '🎬', 'label' => 'Video'],
        $isMap   => ['icon' => '🗺️', 'label' => 'Map'],
        default  => match (strtolower($file->file_extension ?? '')) {
            'pk3'   => ['icon' => '📦', 'label' => 'PK3'],
            'lua'   => ['icon' => '📜', 'label' => 'Lua'],
            'zip'   => ['icon' => '📦', 'label' => 'Archive'],
            'cfg'   => ['icon' => '⚙️', 'label' => 'Config'],
            'way'   => ['icon' => '🤖', 'label' => 'Waypoint'],
            default => ['icon' => '📄', 'label' => strtoupper($file->file_extension ?? 'File')],
        },
    };

    $sizeMb = $file->file_size ? round($file->file_size / 1048576, 1) : null;
@endphp

@extends('layouts.embed')

@section('title', $file->display_title.' — Wolffiles')
@section('mode', $mode)

@section('content')
@if($mode === 'player')
    @php
        $posterUrl   = $file->primaryScreenshot?->url;
        $streamUrl   = route('files.stream', $file->slug);
        $scrubVttUrl = $file->playable_duration_seconds && $file->primaryScreenshot
            ? route('files.scrub-vtt', $file->slug) : null;
    @endphp

    <div class="embed-player-wrap" x-data="videoPlayer()" x-init="init($refs.video, @js($scrubVttUrl))" x-cloak>
        <video x-ref="video" playsinline controls preload="metadata"
            @if($posterUrl) poster="{{ $posterUrl }}" data-poster="{{ $posterUrl }}" @endif>
            <source src="{{ $streamUrl }}" type="{{ $file->playable_mime ?? 'video/mp4' }}">
        </video>
        <a href="{{ route('files.show', $file) }}" target="_blank" rel="noopener" class="embed-brand">
            ▶ wolffiles.eu
        </a>
    </div>

@else
    <div class="embed-card">
        <div class="embed-card-image"
             @if($file->primary_image_url) style="background-image:url('{{ $file->primary_image_url }}');" @endif>
            @unless($file->primary_image_url)
                <div class="placeholder">{{ $typeBadge['icon'] }}</div>
            @endunless
            <div class="type-badge">{{ $typeBadge['icon'] }} {{ $typeBadge['label'] }}</div>
        </div>

        <div class="embed-card-body">
            <h2 class="embed-card-title">{{ $file->display_title }}</h2>

            <div class="embed-card-meta">
                @if($file->category?->parent)
                    <span>{{ $file->category->parent->name }}</span>
                    <span class="sep">›</span>
                @endif
                @if($file->category)
                    <span>{{ $file->category->name }}</span>
                @endif
                @if($sizeMb)
                    <span class="sep">·</span>
                    <span>{{ $sizeMb }} MB</span>
                @endif
            </div>

            <div class="embed-card-stats">
                @if($file->rating_count > 0)
                    <span><span class="star">★</span> {{ number_format($file->average_rating, 1) }} ({{ $file->rating_count }})</span>
                @endif
                <span>⬇ {{ number_format($file->download_count) }}</span>
                <span>👁 {{ number_format($file->view_count) }}</span>
                @if($file->is_featured)
                    <span style="color:#f59e0b;">⭐ Featured</span>
                @endif
            </div>

            <div class="embed-card-actions">
                @if($isMap)
                    <a href="{{ route('files.show', $file) }}" target="_blank" rel="noopener" class="embed-card-cta">
                        🗺️ 3D Preview
                    </a>
                    <a href="{{ route('files.download', $file) }}" target="_blank" rel="noopener" class="embed-card-cta secondary">
                        ⬇ Download
                    </a>
                @else
                    <a href="{{ route('files.download', $file) }}" target="_blank" rel="noopener" class="embed-card-cta">
                        ⬇ Download
                    </a>
                    <a href="{{ route('files.show', $file) }}" target="_blank" rel="noopener" class="embed-brand">
                        ▶ wolffiles.eu
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif
@endsection
