<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $d['player']['name_clean'] }} — wolffiles.eu</title>
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
        width: {{ $width }}px;
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
    .title .name { font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
    .title .flag { display: inline-block; width: 14px; height: 10px; flex-shrink: 0; }
    .kvblock { padding: 6px 8px; border-bottom: 1px solid #333; }
    .kv { display: flex; justify-content: space-between; padding: 2px 0; font-size: 10px; }
    .kv .k { color: #888; }
    .kv .v { color: #fff; font-weight: 500; text-align: right; }
    .kv .v.muted { color: #666; }
    .kv .v.accent { color: #ffcc00; }
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
    .server-row {
        padding: 6px 8px;
        border-bottom: 1px solid #333;
        font-size: 10px;
    }
    .server-row .sv-name {
        color: #ddd;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .server-row.offline .sv-name { color: #555; font-style: italic; }
    .sparkline-wrap {
        position: relative;
        background: linear-gradient(180deg, #0e0e0e, #111);
        border-bottom: 1px solid #333;
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
</style>
</head>
<body>
<div class="embed">

    {{-- Header --}}
    <div class="header">
        <span class="header-dot {{ $d['player']['is_online'] ? 'online' : '' }}"></span>
        <span>Player {{ $d['player']['is_online'] ? '· online' : '· offline' }}</span>
        @if ($d['rank']['family'])
            <span style="color:#555; margin-left:auto; text-transform: uppercase;">{{ $d['rank']['family'] }}</span>
        @endif
    </div>

    {{-- Player title --}}
    <div class="title">
        <a href="{{ url('/players/'.$d['player']['id']) }}" target="_top">
            <div class="name">
                @if ($d['player']['country_code'])
                    <img src="https://flagcdn.com/{{ strtolower($d['player']['country_code']) }}.svg"
                         class="flag" alt="{{ strtoupper($d['player']['country_code']) }}">
                @endif
                <span style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {!! $d['player']['name_html'] ?: e($d['player']['name_clean']) !!}
                </span>
            </div>
        </a>
    </div>

    {{-- Stats (always, all variants) --}}
    <div class="kvblock">
        <div class="kv">
            <span class="k">Rank</span>
            <span class="v">
                @if ($d['rank']['position'])
                    #{{ number_format($d['rank']['position']) }} <span class="muted">/ {{ number_format($d['rank']['total']) }}</span>
                @else
                    <span class="muted">—</span>
                @endif
            </span>
        </div>
        <div class="kv">
            <span class="k">Playtime</span>
            <span class="v">{{ number_format($d['stats']['playtime_hours']) }}h</span>
        </div>
        <div class="kv">
            <span class="k">ELO</span>
            <span class="v">
                @if ($d['stats']['elo'] !== null && $d['stats']['elo'] > 0)
                    {{ number_format($d['stats']['elo']) }}
                    @if ($d['stats']['elo_peak'] && $d['stats']['elo_peak'] > $d['stats']['elo'])
                        <span class="muted" style="font-size:8px;">peak {{ number_format($d['stats']['elo_peak']) }}</span>
                    @endif
                @else
                    <span class="muted">—</span>
                @endif
            </span>
        </div>
        <div class="kv">
            <span class="k">XP</span>
            <span class="v">
                @php
                    $xp = $d['stats']['xp'];
                    if ($xp >= 1_000_000) $xpl = rtrim(rtrim(number_format($xp/1_000_000, 1), '0'), '.').'M';
                    elseif ($xp >= 1_000) $xpl = rtrim(rtrim(number_format($xp/1_000, 1), '0'), '.').'k';
                    else $xpl = (string) $xp;
                @endphp
                {{ $xpl }}
            </span>
        </div>
        <div class="kv">
            <span class="k">Sessions</span>
            <span class="v">{{ number_format($d['stats']['sessions']) }}</span>
        </div>
    </div>

    {{-- Variant 2 & 4: Favorite Server --}}
    @if (in_array($variant, [2, 4]))
        <div class="section-title">Favorite Server</div>
        <div class="server-row {{ $d['favorite_server'] ? '' : 'offline' }}">
            @if ($d['favorite_server'])
                <a href="{{ url('/servers/'.$d['favorite_server']['id']) }}" target="_top" class="sv-name" style="display:block;">
                    {{ $d['favorite_server']['hostname'] }}
                </a>
            @else
                <div class="sv-name">No session data</div>
            @endif
        </div>
    @endif

    {{-- Variant 3 & 4: Currently Playing --}}
    @if (in_array($variant, [3, 4]))
        <div class="section-title">Now Playing</div>
        <div class="server-row {{ $d['current_server'] ? '' : 'offline' }}">
            @if ($d['current_server'])
                <a href="{{ url('/servers/'.$d['current_server']['id']) }}" target="_top" class="sv-name" style="display:block;">
                    {{ $d['current_server']['hostname'] }}
                </a>
            @else
                <div class="sv-name">Not connected</div>
            @endif
        </div>
    @endif

    {{-- ELO Sparkline 24h (if data available) --}}
    @if (!empty($d['elo_history']) && count($d['elo_history']) > 1)
        @php
            $h = $d['elo_history'];
            $max = max($h); $min = min($h);
            $range = max($max - $min, 1.0);
            $w = $width; $svgH = 52; $padTop = 14; $padBottom = 4;
            $usableH = $svgH - $padTop - $padBottom;
            $step = $w / max(count($h) - 1, 1);
            $coords = [];
            foreach ($h as $i => $v) {
                $coords[] = [
                    round($i * $step, 2),
                    round($svgH - $padBottom - (($v - $min) / $range) * $usableH, 2),
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
            $gradId = 'spk_p'.$d['player']['id'];
        @endphp
        <div class="sparkline-wrap">
            <div class="sparkline-caption">
                <span>ELO 24h</span>
                <span>{{ (int) round($max) }}</span>
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

    {{-- Footer --}}
    <div class="footer">
        <a href="{{ url('/') }}" target="_top">wolffiles.eu</a>
    </div>

</div>
</body>
</html>
