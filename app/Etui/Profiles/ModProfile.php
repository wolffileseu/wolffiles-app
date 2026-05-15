<?php

declare(strict_types=1);

namespace App\Etui\Profiles;

abstract class ModProfile
{
    abstract public function slug(): string;

    /**
     * @return array<string, MacroDefinition>  Function-like macros the profile provides
     *                                          out of the box (typically extracted from
     *                                          the mod's menumacros.h). Local #defines
     *                                          in a .menu source override these on name
     *                                          collision in the MacroExpander.
     */
    public function macros(): array
    {
        return [];
    }

    /**
     * @return string[]  Directories the FileSourceResolver tries in order for #include
     *                    resolution. ETLegacy returns ['_includes/etlegacy/', '_includes/']
     *                    so mod-specific headers shadow vanilla ETMain ones when present.
     */
    public function includeSearchPaths(): array
    {
        return [
            storage_path('app/etui/_includes'),
            base_path('tests/fixtures/etui/_includes'),
        ];
    }

    /**
     * @return array<string, mixed>  Symbols pre-set as if by #define before the
     *                                preprocessor sees the source. ETMain leaves
     *                                FUI undefined so menumacros.h's
     *                                  #ifdef FUI / #define WINDOW WINDOW_FUI / #else
     *                                  / #define WINDOW WINDOW_INGAME / #endif
     *                                lands on the ingame variant.
     */
    public function definedSymbols(): array
    {
        return [];
    }

    /**
     * Whether the lexer should collapse `_("text")` calls into a single STRING
     * token. ETLegacy uses gettext-style i18n wrappers in 30+ files; vanilla
     * ETMain has none.
     */
    public function supportsI18nWrapper(): bool
    {
        return false;
    }

    /**
     * @return string[]  Property names that legitimately appear as flag-only
     *                    (no value tokens), e.g. `popup`, `decoration`. The
     *                    parser uses end-of-line as the boundary regardless;
     *                    this list is for downstream lint/UX hints only.
     */
    public function flagProperties(): array
    {
        return [];
    }
}
