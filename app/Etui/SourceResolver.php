<?php

declare(strict_types=1);

namespace App\Etui;

interface SourceResolver
{
    /**
     * Return the textual contents of the include target, or null when the
     * path cannot be resolved by this resolver. Implementations MUST NOT
     * throw on a miss — the preprocessor records it as a ParseError and
     * continues so the editor's live-linter can show every issue at once.
     */
    public function resolve(string $path): ?string;
}
