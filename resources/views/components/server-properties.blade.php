@props([
    'server',
    'size' => 'sm', // xs, sm, md, lg
])

@php
    $sizeClasses = [
        'xs' => 'w-4 h-4',
        'sm' => 'w-5 h-5',
        'md' => 'w-6 h-6',
        'lg' => 'w-8 h-8',
    ];
    $iconClass = $sizeClasses[$size] ?? $sizeClasses['sm'];

    // Build list of active properties with their icon + tooltip.
    // Only properties that evaluate to "on/true/present" are shown,
    // matching how the ET Legacy in-game server browser displays them.
    $properties = [];

    if (($server->bot_count ?? 0) > 0) {
        $properties[] = [
            'icon'    => 'filter_bots.png',
            'tooltip' => 'Bots spielen mit (' . (int) $server->bot_count . ')',
        ];
    }

    if (!empty($server->needs_password)) {
        $properties[] = [
            'icon'    => 'filter_pass.png',
            'tooltip' => 'Server ist passwortgeschützt',
        ];
    }

    if (!empty($server->friendly_fire)) {
        $properties[] = [
            'icon'    => 'filter_ff.png',
            'tooltip' => 'Friendly Fire aktiv',
        ];
    }

    if (!empty($server->antilag)) {
        $properties[] = [
            'icon'    => 'filter_antilag.png',
            'tooltip' => 'Antilag aktiv',
        ];
    }

    if (!empty($server->balanced_teams)) {
        $properties[] = [
            'icon'    => 'filter_balance.png',
            'tooltip' => 'Balanced Teams erzwungen',
        ];
    }

    if (($server->heavy_weapon_restriction ?? 0) > 0) {
        $properties[] = [
            'icon'    => 'filter_weap.png',
            'tooltip' => 'Heavy Weapon Restriction: ' . (int) $server->heavy_weapon_restriction . '%',
        ];
    }

    if (!empty($server->punkbuster)) {
        $properties[] = [
            'icon'    => 'filter_pb.png',
            'tooltip' => 'PunkBuster aktiv',
        ];
    }

    if (!empty($server->anticheat)) {
        // Use mod-specific icon if present, else generic pb icon
        $acIcon = match($server->anticheat) {
            'NxAC'      => 'sv_nxac.png',
            'SilEnT AC' => 'filter_pb.png', // fallback — user can replace later
            default     => 'filter_pb.png',
        };
        $properties[] = [
            'icon'    => $acIcon,
            'tooltip' => $server->anticheat . ' Anticheat',
        ];
    }

    // OS detection from server's OS/version string (e.g. "linux-x86_64", "win-x86", "ET Legacy ... linux-x86_64")
    if (!empty($server->os)) {
        $osLower = strtolower($server->os);
        if (str_contains($osLower, 'linux')) {
            $properties[] = ['icon' => 'filter_linux.png', 'tooltip' => 'Server läuft auf Linux'];
        } elseif (str_contains($osLower, 'win') || str_contains($osLower, 'nt')) {
            $properties[] = ['icon' => 'filter_win.png', 'tooltip' => 'Server läuft auf Windows'];
        } elseif (str_contains($osLower, 'mac') || str_contains($osLower, 'darwin') || str_contains($osLower, 'osx')) {
            $properties[] = ['icon' => 'filter_mac.png', 'tooltip' => 'Server läuft auf Mac'];
        }
    }
@endphp

@if (count($properties) > 0)
    <div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 flex-wrap']) }}>
        @foreach ($properties as $prop)
            @php
                $iconPath = public_path('images/server-properties/' . $prop['icon']);
                $exists = file_exists($iconPath);
            @endphp
            @if ($exists)
                <img
                    src="{{ asset('images/server-properties/' . $prop['icon']) }}"
                    alt="{{ $prop['tooltip'] }}"
                    title="{{ $prop['tooltip'] }}"
                    class="{{ $iconClass }} inline-block"
                    style="image-rendering: pixelated; cursor: help;"
                    loading="lazy"
                />
            @endif
        @endforeach
    </div>
@endif
