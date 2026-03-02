@props(['map'])
@if($map)
@php
    $mapFile = \App\Services\Tracker\MapLinkService::findFile($map);
@endphp
@if($mapFile)
    <a href="{{ route('files.show', $mapFile) }}" class="text-amber-400 hover:text-amber-300" title="Download {{ $map }}">
        {{ $map }}
    </a>
@else
    <span class="text-gray-300">{{ $map }}</span>
@endif
@endif
