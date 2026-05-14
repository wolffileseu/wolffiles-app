<?php

declare(strict_types=1);

use App\Etui\Profiles\EtLegacyProfile;
use App\Etui\Profiles\EtMainProfile;
use App\Etui\Profiles\ProfileRegistry;

it('declares the 8 expected stock macros for vanilla ET:Main', function () {
    $profile = new EtMainProfile();

    expect($profile->slug())->toBe('etmain');
    expect(array_keys($profile->macros()))->toEqualCanonicalizing([
        'WINDOW_FUI', 'WINDOW_INGAME',
        'SUBWINDOW', 'SUBWINDOWBLACK',
        'BUTTON', 'BUTTONEXT',
        'LABEL', 'LABELWHITE',
    ]);
    expect($profile->supportsI18nWrapper())->toBeFalse();
    expect($profile->includeSearchPaths())->toBe(['_includes/']);
});

it('makes ETLegacy inherit ETMain macros while overriding only the legacy-specific knobs', function () {
    $profile = new EtLegacyProfile();

    // Macro set is inherited — Phase 1 doesn't override yet.
    expect(array_keys($profile->macros()))->toEqualCanonicalizing(
        array_keys((new EtMainProfile())->macros()),
    );

    // Legacy-specific differences.
    expect($profile->slug())->toBe('etlegacy');
    expect($profile->supportsI18nWrapper())->toBeTrue();
    expect($profile->includeSearchPaths())->toBe(['_includes/etlegacy/', '_includes/']);
});

it('resolves both profiles through the ProfileRegistry by slug', function () {
    $registry = new ProfileRegistry();

    expect($registry->resolve('etmain'))->toBeInstanceOf(EtMainProfile::class);
    expect($registry->resolve('etlegacy'))->toBeInstanceOf(EtLegacyProfile::class);
    expect($registry->resolve('ETLEGACY'))->toBeInstanceOf(EtLegacyProfile::class);

    expect(fn () => $registry->resolve('nonexistent'))
        ->toThrow(InvalidArgumentException::class);
});
