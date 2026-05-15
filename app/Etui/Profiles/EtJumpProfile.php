<?php

declare(strict_types=1);

namespace App\Etui\Profiles;

/**
 * Phase 2 stub for ET:Jump mod. Inherits the full EtMain macro set so
 * vanilla ETMain fixtures keep working when authored against this slug;
 * Phase 3/4 specialises macros, definedSymbols, etc. once mod-specific
 * stock fixtures land in tests/fixtures/etui/etjump/.
 */
final class EtJumpProfile extends EtMainProfile
{
    public function slug(): string
    {
        return 'etjump';
    }
}
