@php
    $data = app(\App\Services\WallpaperService::class)->pick();
@endphp

@if($data && !empty($data['wallpapers']))
    @if($data['slideshow_enabled'] && count($data['wallpapers']) > 1)
        {{-- Multi-Wallpaper Slideshow with Crossfade --}}
        <div class="fixed inset-0 -z-10 pointer-events-none overflow-hidden"
             x-data="{
                wallpapers: {{ \Illuminate\Support\Js::from($data['wallpapers']) }},
                interval: {{ $data['interval_seconds'] * 1000 }},
                current: 0,
                init() {
                    if (this.wallpapers.length <= 1) return;
                    setInterval(() => {
                        this.current = (this.current + 1) % this.wallpapers.length;
                    }, this.interval);
                }
             }">
            <template x-for="(wp, idx) in wallpapers" :key="wp.id">
                <div class="absolute inset-0 transition-opacity ease-in-out"
                     style="transition-duration: 1500ms; background-size: cover; background-position: center; background-repeat: no-repeat;"
                     :style="`
                        background-image: url(${wp.url});
                        filter: ${wp.overlay_blur > 0 ? 'blur(' + wp.overlay_blur + 'px)' : 'none'};
                        transform: ${wp.overlay_blur > 0 ? 'scale(1.05)' : 'none'};
                        opacity: ${idx === current ? 1 : 0};
                     `"></div>
            </template>
            <div class="absolute inset-0 transition-colors duration-1000"
                 :style="`background-color: ${wallpapers[current].overlay_color}; opacity: ${wallpapers[current].overlay_opacity / 100};`"></div>
        </div>
    @else
        {{-- Single wallpaper (or random pick) --}}
        @php $wp = $data['wallpapers'][0]; @endphp
        <div class="fixed inset-0 -z-10 pointer-events-none overflow-hidden">
            <div class="absolute inset-0"
                 style="background-image: url('{{ $wp['url'] }}'); background-size: cover; background-position: center; background-repeat: no-repeat;@if($wp['overlay_blur'] > 0) filter: blur({{ $wp['overlay_blur'] }}px); transform: scale(1.05);@endif"></div>
            <div class="absolute inset-0"
                 style="background-color: {{ $wp['overlay_color'] }}; opacity: {{ $wp['overlay_opacity'] / 100 }};"></div>
        </div>
    @endif
@endif
