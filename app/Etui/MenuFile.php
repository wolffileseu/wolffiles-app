<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Profiles\ProfileRegistry;

/**
 * The single public entry point for the rest of the application:
 *
 *   $result = MenuFile::parse($source, 'etmain');
 *
 * Wires the four pipeline stages (Preprocessor -> Lexer -> Parser ->
 * MacroExpander) and resolves profile-specific knobs internally so
 * Phase 2-4 callers never need to know the stage boundaries exist.
 *
 * Error bags from every stage are merged into the returned
 * ParseResult::errors, so the editor's live-linter iterates one bag
 * and gets every problem from #include resolution through macro
 * expansion in a single pass.
 */
final class MenuFile
{
    /**
     * @param  array<string, string|int>  $extraDefines  Additional symbols pre-set
     *         as if by #define before processing, merged on top of the profile's
     *         definedSymbols(). The profile's defaults win on a duplicate key —
     *         a caller can't accidentally turn off the profile's canonical FUI
     *         toggle by passing an FUI=0 override.
     */
    public static function parse(
        string $source,
        string $modSlug = 'etmain',
        ?SourceResolver $resolver = null,
        array $extraDefines = [],
    ): ParseResult {
        $profile = (new ProfileRegistry())->resolve($modSlug);

        $defines = array_merge($extraDefines, $profile->definedSymbols());

        $resolver ??= new FileSourceResolver($profile->includeSearchPaths());

        $ppResult = (new Preprocessor($resolver))->process($source, $defines);

        $tokens = (new Lexer($profile->supportsI18nWrapper()))->tokenize($ppResult->source);

        $parseResult = (new Parser())->parse($tokens);

        foreach ($ppResult->errors->all() as $e) {
            $parseResult->errors->push($e);
        }

        return (new MacroExpander($profile))->expand($parseResult, $ppResult->localMacros);
    }
}
