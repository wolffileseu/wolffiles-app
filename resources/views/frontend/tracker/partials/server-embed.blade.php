<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $d['server']['hostname_clean'] }} — wolffiles.eu</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 11px;
        line-height: 1.4;
        color: #cccccc;
        background: #1a1a1a;
        -webkit-font-smoothing: antialiased;
    }
    a { color: inherit; text-decoration: none; }
    a:hover { filter: brightness(1.3); }
    .embed {
        width: {{ $opts['width'] }}px;
        background: #1a1a1a;
        border: 1px solid #444;
    }
    .header {
        background: linear-gradient(180deg, #2a2a2a 0%, #1a1a1a 100%);
        padding: 6px 8px;
        font-size: 10px;
        color: #888;
        border-bottom: 1px solid #333;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .header-dot { width: 6px; height: 6px; border-radius: 50%; background: #888; display: inline-block; }
    .header-dot.online { background: #4cdd4c; box-shadow: 0 0 4px rgba(76,221,76,0.6); }
    .title {
        background: #222;
        padding: 6px 8px;
        line-height: 1.3;
        border-bottom: 1px solid #333;
        word-break: break-word;
    }
    .title a { display: block; }
    .title a:hover { background: #262626; }
    .title .hostname { font-size: 12px; font-weight: 600; }
    .title .hostname span { font-weight: 600; }
    .kvblock { padding: 6px 8px; border-bottom: 1px solid #333; }
    .kv { display: flex; justify-content: space-between; padding: 2px 0; font-size: 10px; }
    .kv .k { color: #888; }
    .kv .v { color: #fff; font-weight: 500; text-align: right; }
    .kv .v.online { color: #4cdd4c; }
    .kv .v.offline { color: #dd4c4c; }
    .section-title {
        background: #222;
        color: #ffcc00;
        font-size: 9px;
        font-weight: 600;
        padding: 4px 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #333;
    }
    .players { padding: 4px 8px; border-bottom: 1px solid #333; }
    .players ol { list-style: none; counter-reset: p; }
    .players li {
        counter-increment: p;
        display: flex;
        justify-content: space-between;
        padding: 2px 0;
        font-size: 10px;
        border-bottom: 1px dashed #2a2a2a;
    }
    .players li:last-child { border-bottom: none; }
    .players li::before {
        content: counter(p) ".";
        color: #666;
        margin-right: 6px;
        display: inline-block;
        min-width: 14px;
    }
    .players .name { flex: 1; color: #ddd; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .players .meta { color: #888; font-size: 9px; margin-left: 6px; }
    .map-thumb {
        width: 100%;
        aspect-ratio: 16 / 9;
        background: #0a0a0a center/cover no-repeat;
        border-bottom: 1px solid #333;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #555;
        font-size: 10px;
    }
    .sparkline-wrap {
        position: relative;
        background: linear-gradient(180deg, #0e0e0e, #111);
        border-bottom: 1px solid #333;
        padding: 0;
    }
    .sparkline-caption {
        position: absolute;
        top: 4px;
        left: 8px;
        right: 8px;
        display: flex;
        justify-content: space-between;
        font-size: 8px;
        color: #777;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        pointer-events: none;
        z-index: 2;
    }
    .sparkline { display: block; width: 100%; height: 52px; }
    .footer {
        background: #111;
        font-size: 9px;
        text-align: center;
        padding: 4px 8px;
    }
    .footer a { color: #ffcc00; }
    .empty { color: #555; font-size: 10px; text-align: center; padding: 8px; font-style: italic; }
</style>
</head>
<body>
<div class="embed">

    {{-- Header --}}
    <div class="header">
        <span class="header-dot {{ $d['server']['is_online'] ? 'online' : '' }}"></span>
        <span>Wolfenstein: Enemy Territory</span>
        @if ($d['server']['mod_name'])
            <span style="color:#555; margin-left:auto;">{{ $d['server']['mod_name'] }}</span>
        @endif
    </div>

    {{-- Server title (clickable → server page) --}}
    <div class="title">
        <a href="{{ url('/servers/'.$d['server']['id']) }}" target="_top">
            <div class="hostname">{!! $d['server']['hostname_html'] ?: e($d['server']['hostname_clean']) !!}</div>
        </a>
    </div>

    {{-- Server basics --}}
    <div class="kvblock">
        <div class="kv"><span class="k">IP</span><span class="v">{{ $d['server']['ip'] }}:{{ $d['server']['port'] }}</span></div>
        <div class="kv"><span class="k">Status</span><span class="v {{ $d['server']['is_online'] ? 'online' : 'offline' }}">{{ $d['server']['is_online'] ? 'Online' : 'Offline' }}</span></div>
        <div class="kv"><span class="k">Players</span><span class="v">{{ $d['server']['current_players'] }} / {{ $d['server']['max_players'] }}</span></div>
        <div class="kv"><span class="k">Map</span><span class="v">{{ $d['server']['current_map'] ?: '—' }}</span></div>
        @if ($d['rank']['position'])
            <div class="kv"><span class="k">Rank</span><span class="v">#{{ $d['rank']['position'] }} <span style="color:#666;">/ {{ $d['rank']['total'] }}</span></span></div>
        @endif
    </div>

    {{-- Map screenshot or placeholder --}}
    @if ($opts['show_map'])
        @if ($d['map_thumb'])
            <div class="map-thumb" style="background-image: url('{{ $d['map_thumb'] }}');"></div>
        @elseif ($d['server']['current_map'])
            <div class="map-thumb">
                <span style="display:inline-flex; align-items:center; gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.6;">
                        <polygon points="1 6 8 3 16 6 23 3 23 18 16 21 8 18 1 21 1 6"/>
                        <line x1="8" y1="3" x2="8" y2="18"/>
                        <line x1="16" y1="6" x2="16" y2="21"/>
                    </svg>
                    <span>{{ $d['server']['current_map'] }}</span>
                </span>
            </div>
        @endif
    @endif

    {{-- 24h sparkline: taller, gradient fill, smoother line --}}
    @if (!empty($d['history']))
        @php
            $h = $d['history'];
            $max = max(max($h), 1);
            $w = $opts['width'];
            $svgH = 52; $padTop = 14; $padBottom = 4;
            $usableH = $svgH - $padTop - $padBottom;
            $step = $w / max(count($h) - 1, 1);
            $coords = [];
            foreach ($h as $i => $p) {
                $coords[] = [
                    round($i * $step, 2),
                    round($svgH - $padBottom - ($p / $max) * $usableH, 2),
                ];
            }
            $linePath = '';
            foreach ($coords as $i => [$x, $y]) {
                $linePath .= ($i === 0 ? 'M' : ' L') . "$x,$y";
            }
            $fillPath = 'M0,'.$svgH.' L'.$coords[0][0].','.$coords[0][1];
            foreach (array_slice($coords, 1) as [$x, $y]) {
                $fillPath .= " L$x,$y";
            }
            $fillPath .= " L$w,$svgH Z";
            $gradId = 'spk'.$d['server']['id'];
        @endphp
        <div class="sparkline-wrap">
            <div class="sparkline-caption">
                <span>Last 24h</span>
                <span>peak {{ $max }}</span>
            </div>
            <svg class="sparkline" viewBox="0 0 {{ $w }} {{ $svgH }}" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="{{ $gradId }}" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#ffcc00" stop-opacity="0.55"/>
                        <stop offset="100%" stop-color="#ffcc00" stop-opacity="0.02"/>
                    </linearGradient>
                </defs>
                <path d="{{ $fillPath }}" fill="url(#{{ $gradId }})"/>
                <path d="{{ $linePath }}" fill="none" stroke="#ffcc00" stroke-width="1.5"
                      stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
            </svg>
        </div>
    @endif

    {{-- Online players (sorted by session duration, longest first) --}}
    @if ($opts['show_current'])
        <div class="section-title">Online players</div>
        <div class="players">
            @if (count($d['current_players']))
                <ol>
                    @foreach ($d['current_players'] as $p)
                        <li>
                            <span class="name">{!! $p['html'] ?: e($p['name']) !!}</span>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="empty">Server is empty</div>
            @endif
        </div>
    @endif

    {{-- Top players all-time --}}
    @if ($opts['show_top'])
        <div class="section-title">Top 8 players</div>
        <div class="players">
            @if (count($d['top_players']))
                <ol>
                    @foreach ($d['top_players'] as $p)
                        <li>
                            <span class="name">{!! $p['html'] ?: e($p['name']) !!}</span>
                            <span class="meta">
                                @php
                                    $xp = $p['xp'];
                                    if ($xp >= 1_000_000) $xpl = number_format($xp/1_000_000, 1).'m';
                                    elseif ($xp >= 1_000) $xpl = number_format($xp/1_000, 1).'k';
                                    else $xpl = (string) $xp;
                                @endphp
                                {{ $xpl }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="empty">No data yet</div>
            @endif
        </div>
    @endif

    {{-- Footer: link to homepage --}}
    <div class="footer">
        <a href="{{ url('/') }}" target="_top">wolffiles.eu</a>
    </div>

</div>
</body>
</html>
