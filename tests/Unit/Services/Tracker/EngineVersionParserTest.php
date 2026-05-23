<?php

use App\Services\Tracker\EngineVersionParser;

beforeEach(function () {
    $this->parser = new EngineVersionParser();
});

dataset('engine_strings', [
    'ET 2.60b' => [
        'ET 2.60b linux-i386 May  8 2006',
        'et_vanilla', '2.60b', 'linux-i386', '2006-05-08', false, 'ET 2.60b',
    ],
    'ET 2.60' => [
        'ET 2.60 linux-i386 Mar 10 2005',
        'et_vanilla', '2.60', 'linux-i386', '2005-03-10', false, 'ET 2.60',
    ],
    'ET 2.60d' => [
        'ET 2.60d linux-i386 Sep 25 2011',
        'et_vanilla', '2.60d', 'linux-i386', '2011-09-25', false, 'ET 2.60d',
    ],
    'ET 2.60e' => [
        'ET 2.60e linux-x86_64 Apr 20 2026',
        'et_vanilla', '2.60e', 'linux-x86_64', '2026-04-20', false, 'ET 2.60e',
    ],
    'ETL stable 2.83.2' => [
        'ET Legacy v2.83.2 linux-x86_64 Jan 19 2025',
        'et_legacy', '2.83.2', 'linux-x86_64', '2025-01-19', false, 'ET Legacy v2.83.2',
    ],
    'ETL 2.84.0' => [
        'ET Legacy v2.84.0 linux-x86_64 May 18 2026',
        'et_legacy', '2.84.0', 'linux-x86_64', '2026-05-18', false, 'ET Legacy v2.84.0',
    ],
    'ETL dev build with hash' => [
        'ET Legacy v2.83.2-563-gd4ba332 linux-x86_64 Mar  6 2026',
        'et_legacy', '2.83.2', 'linux-x86_64', '2026-03-06', true, 'ET Legacy v2.83.2 (dev)',
    ],
    'ETL dirty (no v prefix, two-part version)' => [
        'ET Legacy 2.83-dirty linux-x86_64 Apr 21 2026',
        'et_legacy', '2.83', 'linux-x86_64', '2026-04-21', true, 'ET Legacy v2.83 (dev)',
    ],
    'ETDS 0.7.4' => [
        'ET 3.00 - TB 0.7.4 linux-i386',
        'et_truebox', '0.7.4', 'linux-i386', null, false, 'ET 3.00 - TB 0.7.4',
    ],
    'ETDS 0.6.7' => [
        'ET 3.00 - TB 0.6.7 linux-i386',
        'et_truebox', '0.6.7', 'linux-i386', null, false, 'ET 3.00 - TB 0.6.7',
    ],
    'ETTV' => [
        'ETTV 1.0 linux-i386 Apr 10 2007',
        'ettv', '1.0', 'linux-i386', '2007-04-10', false, 'ETTV 1.0',
    ],
    'iortcw' => [
        'iortcw 1.51c-MP linux-x86_64 Mar 16 2019',
        'rtcw_iortcw', '1.51c-MP', 'linux-x86_64', '2019-03-16', false, 'iortcw 1.51c-MP',
    ],
    'RtcwPro' => [
        'RtcwPro 1.4.0.3 linux-i386 Sep 13 2025',
        'rtcw_pro', '1.4.0.3', 'linux-i386', '2025-09-13', false, 'RtcwPro 1.4.0.3',
    ],
    'RTCWCoop stable' => [
        'RTCWCoop 1.0.2 linux-x86_64 Aug 23 2017',
        'rtcw_coop', '1.0.2', 'linux-x86_64', '2017-08-23', false, 'RTCWCoop 1.0.2',
    ],
    'RTCWCoop dev (GIT)' => [
        'RTCWCoop 1.0.3_GIT_9bc5397-2022-10-05 win_mingw64-x64 Oct  5 2022',
        'rtcw_coop', '1.0.3', 'win_mingw64-x64', '2022-10-05', true, 'RTCWCoop 1.0.3 (dev)',
    ],
    'WolfX' => [
        'WolfX 1.0 linux-i386 Feb  6 2022',
        'rtcw_wolfx', '1.0', 'linux-i386', '2022-02-06', false, 'WolfX 1.0',
    ],
    'Wolf 1.41b-MP' => [
        'Wolf 1.41b-MP win-x86 May  8 2006',
        'rtcw_vanilla', '1.41b-MP', 'win-x86', '2006-05-08', false, 'Wolf 1.41b-MP',
    ],
    'Wolf 1.0/WolfSE' => [
        'Wolf 1.0/WolfSE win-x86 Mar 11 2026',
        'rtcw_vanilla', '1.0/WolfSE', 'win-x86', '2026-03-11', false, 'Wolf 1.0/WolfSE',
    ],
    'Wolf 1.0/WolfSE with version' => [
        'Wolf 1.0/WolfSE 1.0.0.7 win-x86 Dec 27 2025',
        'rtcw_vanilla', '1.0/WolfSE 1.0.0.7', 'win-x86', '2025-12-27', false, 'Wolf 1.0/WolfSE 1.0.0.7',
    ],
    'rtcw generic' => [
        'rtcw 1.x win-x86 Dec 18 2023',
        'rtcw_generic', '1.x', 'win-x86', '2023-12-18', false, 'rtcw 1.x',
    ],
    'Q3' => [
        'Q3 1.32c linux-i386 May  8 2006',
        'quake3', '1.32c', 'linux-i386', '2006-05-08', false, 'Q3 1.32c',
    ],
    'Platform only' => [
        'linux-x86_64',
        'unknown', null, 'linux-x86_64', null, false, null,
    ],
    'win-x86 platform only' => [
        'win-x86',
        'unknown', null, 'win-x86', null, false, null,
    ],
    'Empty string' => [
        '',
        null, null, null, null, false, null,
    ],
]);

it('parses engine version strings correctly', function (
    string $input,
    ?string $family,
    ?string $version,
    ?string $platform,
    ?string $buildDate,
    bool $isDev,
    ?string $display
) {
    $r = $this->parser->parse($input);
    expect($r['engine_family'])->toBe($family);
    expect($r['engine_version'])->toBe($version);
    expect($r['engine_platform'])->toBe($platform);
    expect($r['engine_build_date'])->toBe($buildDate);
    expect($r['engine_is_dev_build'])->toBe($isDev);
    expect($r['engine_display'])->toBe($display);
})->with('engine_strings');

it('handles null input gracefully', function () {
    $r = $this->parser->parse(null);
    expect($r['engine_family'])->toBeNull();
    expect($r['engine_version'])->toBeNull();
});
