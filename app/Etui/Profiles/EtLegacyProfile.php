<?php

declare(strict_types=1);

namespace App\Etui\Profiles;

/**
 * ET:Legacy mod stub for Phase 1.
 *
 * Inherits the macro set from EtMain (so unmodified vanilla macros keep
 * working) and layers ET:Legacy specifics on top: gettext i18n wrappers
 * and an extra include search path so mod-specific headers shadow the
 * vanilla ETMain ones when present.
 *
 * Phase 2 will override macros() with ET:Legacy-specific additions
 * (extra UI widgets, mod-only buttons) once we extract them from the
 * legacy .pk3 headers.
 */
final class EtLegacyProfile extends EtMainProfile
{
    public function slug(): string
    {
        return 'etlegacy';
    }

    public function includeSearchPaths(): array
    {
        return ['_includes/etlegacy/', '_includes/'];
    }

    public function supportsI18nWrapper(): bool
    {
        return true;
    }
}
