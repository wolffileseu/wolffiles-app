@props(['code' => '', 'country' => '', 'size' => '20'])
@php
$code = strtolower(trim($code));
@endphp
@if($code)
<img src="https://flagcdn.com/{{ $code }}.svg"
     alt="{{ $country ?? strtoupper($code) }}"
     title="{{ $country ?? strtoupper($code) }}"
     width="{{ $size }}"
     height="{{ round($size * 0.75) }}"
     class="inline-block rounded-sm"
     style="width: {{ $size }}px; height: auto;"
     loading="lazy"
     onerror="this.style.display='none'">
@endif
