@props(['map' => null])
@if($map)
@php
    $disk = Storage::disk('s3');
    $path = 'levelshots/' . strtolower($map) . '.jpg';
    $url = $disk->exists($path) ? $disk->url($path) : null;
@endphp
@if($url)
    <img src="{{ $url }}" alt="{{ $map }}" class="w-full rounded-lg" loading="lazy">
@endif
@endif
