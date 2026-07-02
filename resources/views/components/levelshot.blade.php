@props(['map' => null])
@if($map)
    <img src="https://cdn.wolffiles.eu/levelshots/{{ strtolower($map) }}.jpg"
         alt="{{ $map }}"
         class="w-full rounded-lg"
         loading="lazy"
         onerror="this.style.display='none'">
@endif
