<?php

namespace App\Services\Tracker;

/**
 * Parses the raw "os" string emitted by ET / RtCW servers into normalized
 * engine_family / engine_version / engine_platform / engine_build_date fields.
 *
 * Examples:
 *   "ET Legacy v2.83.2 linux-x86_64 Jan 19 2025"
 *     -> [et_legacy, 2.83.2, linux-x86_64, 2025-01-19, dev=false]
 *   "ET Legacy v2.83.2-563-gd4ba332 linux-x86_64 Mar  6 2026"
 *     -> [et_legacy, 2.83.2, linux-x86_64, 2026-03-06, dev=true]
 *   "ET 3.00 - TB 0.7.4 linux-i386"
 *     -> [et_truebox, 0.7.4, linux-i386, null, dev=false]
 *   "linux-x86_64"
 *     -> [unknown, null, linux-x86_64, null, dev=false]
 */
class EngineVersionParser
{
    public const FAMILY_LABELS = [
        'et_legacy'     => 'ET Legacy',
        'et_truebox'    => 'ET 3.00 - TB (ETDS)',
        'et_vanilla'    => 'ET 2.60 (Vanilla)',
        'ettv'          => 'ETTV',
        'rtcw_iortcw'   => 'iortcw',
        'rtcw_pro'      => 'RtcwPro',
        'rtcw_coop'     => 'RTCWCoop',
        'rtcw_wolfx'    => 'WolfX',
        'rtcw_vanilla'  => 'RtCW Vanilla',
        'rtcw_generic'  => 'RtCW (generic)',
        'quake3'        => 'Quake III',
        'quake3_ioq3'   => 'ioquake3',
        'cod4'          => 'Call of Duty 4',
        'unknown'       => 'Unknown',
    ];

    private const PLATFORM_PATTERN = '(linux-i386(?:-custom)?|linux-x86_64|linux-aarch64|linux-amd64|win-x86|win-x64|win_msvc-x64|win_mingw64-x64|win_mingw32-x86|darwin[\w\-]*)';

    /**
     * @return array{engine_family:?string,engine_version:?string,engine_platform:?string,engine_build_date:?string,engine_is_dev_build:bool,engine_display:?string}
     */
    public function parse(?string $osString): array
    {
        $result = [
            'engine_family'       => null,
            'engine_version'      => null,
            'engine_platform'     => null,
            'engine_build_date'   => null,
            'engine_is_dev_build' => false,
            'engine_display'      => null,
        ];

        $os = trim((string) $osString);
        if ($os === '') {
            return $result;
        }

        // 1) Build-Datum am Ende ("May  8 2006", "Jan 19 2025") — Monat kann 1 oder 2 Stellen Tag haben
        if (preg_match('/\s+(?<build>[A-Z][a-z]{2}\s+\d{1,2}\s+\d{4})$/', $os, $m)) {
            $result['engine_build_date'] = $this->normalizeBuildDate($m['build']);
            $os = rtrim(substr($os, 0, -strlen($m[0])));
        }

        // 2) Plattform am Ende ODER als einzelnes Token
        if (preg_match('/\s+' . self::PLATFORM_PATTERN . '$/', $os, $m)) {
            $result['engine_platform'] = $m[1];
            $os = rtrim(substr($os, 0, -strlen($m[0])));
        } elseif (preg_match('/^' . self::PLATFORM_PATTERN . '$/', $os, $m)) {
            $result['engine_platform'] = $m[1];
            $result['engine_family'] = 'unknown';
            return $result;
        }

        if ($os === '') {
            $result['engine_family'] = 'unknown';
            return $result;
        }

        // 3) Engine + Version
        [$family, $version, $isDev] = $this->identifyEngine($os);

        $result['engine_family']       = $family;
        $result['engine_version']      = $version;
        $result['engine_is_dev_build'] = $isDev;
        $result['engine_display']      = $this->buildDisplay($family, $version, $isDev);

        return $result;
    }

    /** @return array{0:string,1:?string,2:bool} */
    private function identifyEngine(string $s): array
    {
        // ET Legacy — auch ohne "v" und mit -dirty / -NNN-gHASH
        if (preg_match('/^ET Legacy v?(?<v>\d+\.\d+(?:\.\d+)?)(?<dev>-\d+-g[a-f0-9]+)?(?<dirty>-dirty)?$/', $s, $m)) {
            return ['et_legacy', $m['v'], !empty($m['dev']) || !empty($m['dirty'])];
        }

        // ETDS / Truebox
        if (preg_match('/^ET 3\.00 - TB (?<v>\d+\.\d+(?:\.\d+)?)$/', $s, $m)) {
            return ['et_truebox', $m['v'], false];
        }

        // ET 2.60 vanilla
        if (preg_match('/^ET (?<v>2\.60[a-z]?)$/', $s, $m)) {
            return ['et_vanilla', $m['v'], false];
        }

        // ETTV
        if (preg_match('/^ETTV (?<v>\S+)$/', $s, $m)) {
            return ['ettv', $m['v'], false];
        }

        // iortcw
        if (preg_match('/^iortcw (?<v>\S+)$/', $s, $m)) {
            return ['rtcw_iortcw', $m['v'], false];
        }

        // RtcwPro
        if (preg_match('/^RtcwPro (?<v>\S+)$/', $s, $m)) {
            return ['rtcw_pro', $m['v'], false];
        }

        // RTCWCoop (mit optionalem _GIT_-Suffix für dev)
        if (preg_match('/^RTCWCoop (?<v>\S+)$/', $s, $m)) {
            $v = $m['v'];
            $isDev = str_contains($v, '_GIT_');
            if ($isDev) {
                $v = preg_replace('/_GIT_.*$/', '', $v);
            }
            return ['rtcw_coop', $v, $isDev];
        }

        // WolfX
        if (preg_match('/^WolfX (?<v>\S+)$/', $s, $m)) {
            return ['rtcw_wolfx', $m['v'], false];
        }

        // Wolf / WolfSE — alles was mit "Wolf " anfängt (greedy für Sondernamen wie "1.0/WolfSE 1.0.0.7")
        if (preg_match('/^Wolf (?<v>.+)$/', $s, $m)) {
            return ['rtcw_vanilla', trim($m['v']), false];
        }

        // rtcw generic
        if (preg_match('/^rtcw (?<v>\S+)$/', $s, $m)) {
            return ['rtcw_generic', $m['v'], false];
        }

        // Quake III
        if (preg_match('/^Q3 (?<v>\S+)$/', $s, $m)) {
            return ['quake3', $m['v'], false];
        }

        // ioquake3 — "ioq3 1.36+u20221123.70d07d9+dfsg-1/Debian"
        if (preg_match('/^ioq3 (?<v>\S+(?:\s*\S+)*?)$/', $s, $m)) {
            return ['quake3_ioq3', trim($m['v']), false];
        }

        // Call of Duty 4 — "CoD4 X - linux-i386-custom build 1221"
        if (preg_match('/^CoD4(?:\s+X)?(?:\s*-\s*)?(?<rest>.*)$/i', $s, $m)) {
            $rest = trim($m['rest']);
            if (preg_match('/build\s+(?<v>\d+)/', $rest, $bm)) {
                return ['cod4', 'build ' . $bm['v'], false];
            }
            return ['cod4', $rest ?: null, false];
        }

        return ['unknown', null, false];
    }

    private function buildDisplay(string $family, ?string $version, bool $isDev): ?string
    {
        if ($version === null) {
            return null;
        }
        $dev = $isDev ? ' (dev)' : '';

        return match ($family) {
            'et_legacy'    => "ET Legacy v{$version}{$dev}",
            'et_truebox'   => "ET 3.00 - TB {$version}",
            'et_vanilla'   => "ET {$version}",
            'ettv'         => "ETTV {$version}",
            'rtcw_iortcw'  => "iortcw {$version}",
            'rtcw_pro'     => "RtcwPro {$version}",
            'rtcw_coop'    => "RTCWCoop {$version}{$dev}",
            'rtcw_wolfx'   => "WolfX {$version}",
            'rtcw_vanilla' => "Wolf {$version}",
            'rtcw_generic' => "rtcw {$version}",
            'quake3'       => "Q3 {$version}",
            'quake3_ioq3'  => "ioq3 {$version}",
            'cod4'         => "CoD4 {$version}",
            default        => $version,
        };
    }

    private function normalizeBuildDate(string $raw): ?string
    {
        $clean = preg_replace('/\s+/', ' ', trim($raw));
        $dt = \DateTime::createFromFormat('M j Y', $clean);
        return $dt ? $dt->format('Y-m-d') : null;
    }
}
