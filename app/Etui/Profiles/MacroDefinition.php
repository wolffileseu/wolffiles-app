<?php

declare(strict_types=1);

namespace App\Etui\Profiles;

/**
 * A function-like macro definition usable by the MacroExpander.
 *
 * `bodyTemplate` is raw .menu source where the parameter names appear
 * as identifiers and where C-preprocessor `##` may appear for
 * token-paste (the MacroExpander handles `##` post-substitution).
 */
final readonly class MacroDefinition
{
    /**
     * @param  string[]  $paramNames
     */
    public function __construct(
        public string $name,
        public array $paramNames,
        public string $bodyTemplate,
    ) {}
}
