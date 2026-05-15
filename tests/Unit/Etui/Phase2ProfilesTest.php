<?php

declare(strict_types=1);

use App\Etui\Lexer;
use App\Etui\MenuFile;
use App\Etui\Profiles\EtJumpProfile;
use App\Etui\Profiles\EtLegacyProfile;
use App\Etui\Profiles\EtMainProfile;
use App\Etui\Profiles\ProfileRegistry;

it('registers all nine Phase-2 mod profiles by slug', function () {
    $registry = new ProfileRegistry();
    $slugs = $registry->slugs();

    expect($slugs)->toEqualCanonicalizing([
        'etmain', 'etlegacy', 'etjump', 'silent',
        'jaymod', 'nq', 'nitmod', 'tce', 'etc',
    ]);
});

it('all seven stub profiles inherit ETMain macros so vanilla source still parses', function () {
    $registry = new ProfileRegistry();
    $baseline = array_keys((new EtMainProfile())->macros());

    foreach (['etjump', 'silent', 'jaymod', 'nq', 'nitmod', 'tce', 'etc'] as $slug) {
        $profile = $registry->resolve($slug);
        expect(array_keys($profile->macros()))->toEqualCanonicalizing(
            $baseline,
            "Profile '{$slug}' should inherit ETMain macros",
        );
    }
});

it('MenuFile::parse accepts a ModProfile instance directly without registry lookup', function () {
    // Phase-2 DB-backed mods carry an FQN in $mod->parser_profile and resolve
    // to a ready-made profile via EtuiMod::$profile. The facade must take it
    // verbatim — registry lookup would fail for a custom slug like "myetmain".
    $customProfile = new class extends EtMainProfile {
        public function slug(): string { return 'myetmain'; }
    };

    $result = MenuFile::parse('menuDef { name "x" }', $customProfile);

    expect($result->errors->isEmpty())->toBeTrue();
    expect($result->menus)->toHaveCount(1);
});
