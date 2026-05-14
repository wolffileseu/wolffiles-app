<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Errors\ErrorBag;
use App\Etui\Profiles\MacroDefinition;

/**
 * The Preprocessor's full output: expanded source string plus the
 * function-like macros it collected from #define directives.
 * Function-like locals win over profile-provided macros of the same
 * name in the MacroExpander — a .menu file can override a stock macro
 * just by defining its own version inline.
 */
final readonly class PreprocessResult
{
    /**
     * @param  array<string, MacroDefinition>  $localMacros
     */
    public function __construct(
        public string $source,
        public array $localMacros,
        public ErrorBag $errors,
    ) {}
}
