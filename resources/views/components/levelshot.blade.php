@props(['map' => null])
@if($map)
    <img src="/images/map-thumbs/{{ strtolower($map) }}.jpg"
         alt="{{ $map }}"
         class="w-full rounded-lg"
         loading="lazy"
         onerror="this.style.display='none'">
@endif
