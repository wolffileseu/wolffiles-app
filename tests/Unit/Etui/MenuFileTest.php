<?php

declare(strict_types=1);

use App\Etui\Errors\ParseError;
use App\Etui\FileSourceResolver;
use App\Etui\MenuFile;
use App\Etui\SourceResolver;

final class MenuFileEmptyResolver implements SourceResolver
{
    public function resolve(string $path): ?string
    {
        return null;
    }
}

it('parses a complete stock fixture end-to-end without errors', function () {
    $fixtureRoot = realpath(__DIR__ . '/../../fixtures/etui');
    $resolver = new FileSourceResolver([$fixtureRoot . '/_includes/']);

    $source = file_get_contents($fixtureRoot . '/etmain/popup_hostgameerrormessage.menu');

    $result = MenuFile::parse($source, 'etmain', $resolver);

    $hardErrors = array_filter(
        $result->errors->all(),
        fn (ParseError $e) => $e->severity === ParseError::SEVERITY_ERROR,
    );
    expect($hardErrors)->toBeEmpty();
    expect($result->menus)->not->toBeEmpty();
});

it('uses the etlegacy profile correctly — i18n collapse + extended include paths', function () {
    // Inline source so the test owns exactly what it asserts. ETLegacy's
    // profile flips supportsI18nWrapper(true), so _("text") collapses to a
    // single STRING token instead of staying as IDENT LPAREN STRING RPAREN.
    $source = <<<'MENU'
menuDef {
    name "test"
    text _("YES")
}
MENU;

    $result = MenuFile::parse($source, 'etlegacy', new MenuFileEmptyResolver());

    $textProperty = null;
    foreach ($result->menus[0]->children as $child) {
        if ($child instanceof App\Etui\Ast\PropertyNode && $child->name === 'text') {
            $textProperty = $child;
            break;
        }
    }

    expect($textProperty)->not->toBeNull();
    // Single collapsed STRING with the isI18n flag set — proof the lexer
    // was constructed via the etlegacy profile's supportsI18nWrapper().
    expect($textProperty->values)->toHaveCount(1);
    expect($textProperty->values[0]->type)->toBe(App\Etui\TokenType::STRING);
    expect($textProperty->values[0]->value)->toBe('YES');
    expect($textProperty->values[0]->isI18n)->toBeTrue();
});

it('propagates errors from every pipeline stage into ParseResult::errors', function () {
    // Three deliberate problems, one per stage:
    //   - preprocessor: #include of a path the resolver can't find
    //   - parser:       garbage token at top level after the include
    //   - macro:        call to a macro the EtMain profile doesn't know
    $source = <<<'MENU'
#include "missing.h"
INVALID_GARBAGE_TOP_LEVEL_TOKEN
menuDef {
    NOPE_NOT_A_REAL_MACRO(1, 2)
}
MENU;

    $result = MenuFile::parse($source, 'etmain', new MenuFileEmptyResolver());

    $hardErrors = array_filter(
        $result->errors->all(),
        fn (ParseError $e) => $e->severity === ParseError::SEVERITY_ERROR,
    );

    // Three stages, three errors — preprocessor (include), parser (top-level
    // garbage), expander (unknown macro). The bag carries them all forward
    // so the editor's linter can highlight every line in one pass.
    expect(count($hardErrors))->toBeGreaterThanOrEqual(3);

    $messages = implode('|', array_map(fn ($e) => $e->message, $hardErrors));
    expect($messages)->toContain('missing.h');
    expect($messages)->toContain('INVALID_GARBAGE_TOP_LEVEL_TOKEN');
    expect($messages)->toContain('NOPE_NOT_A_REAL_MACRO');
});
