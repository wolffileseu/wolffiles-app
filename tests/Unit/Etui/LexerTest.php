<?php

declare(strict_types=1);

use App\Etui\Lexer;
use App\Etui\Token;
use App\Etui\TokenType;

it('returns only EOF on empty input', function () {
    $tokens = (new Lexer())->tokenize('');

    expect($tokens)->toHaveCount(1);
    expect($tokens[0])->toBeInstanceOf(Token::class);
    expect($tokens[0]->type)->toBe(TokenType::EOF);
});

it('lexes a double-quoted string', function () {
    $tokens = (new Lexer())->tokenize('"hello"');

    expect($tokens)->toHaveCount(2);
    expect($tokens[0]->type)->toBe(TokenType::STRING);
    expect($tokens[0]->value)->toBe('hello');
    expect($tokens[0]->lexeme)->toBe('"hello"');
    expect($tokens[0]->isI18n)->toBeFalse();
});

it('lexes integer, float and negative numbers as single NUMBER tokens', function () {
    $tokens = (new Lexer())->tokenize('12 .25 -3.14 0');

    expect($tokens)->toHaveCount(5);
    foreach (range(0, 3) as $i) {
        expect($tokens[$i]->type)->toBe(TokenType::NUMBER);
    }
    expect($tokens[0]->value)->toBe(12);
    expect($tokens[1]->value)->toBe(0.25);
    expect($tokens[2]->value)->toBe(-3.14);
    expect($tokens[3]->value)->toBe(0);
});

it('lexes braces, parens, commas', function () {
    $tokens = (new Lexer())->tokenize('{ } ( ) ,');

    $types = array_map(fn (Token $t) => $t->type, array_slice($tokens, 0, 5));
    expect($types)->toBe([
        TokenType::LBRACE,
        TokenType::RBRACE,
        TokenType::LPAREN,
        TokenType::RPAREN,
        TokenType::COMMA,
    ]);
});

it('lexes identifiers including UPPER_SNAKE_CASE', function () {
    $tokens = (new Lexer())->tokenize('menuDef name WINDOW_STYLE_FILLED onESC _underscore');

    expect($tokens[0]->type)->toBe(TokenType::IDENT);
    expect($tokens[0]->value)->toBe('menuDef');
    expect($tokens[1]->value)->toBe('name');
    expect($tokens[2]->value)->toBe('WINDOW_STYLE_FILLED');
    expect($tokens[3]->value)->toBe('onESC');
    expect($tokens[4]->value)->toBe('_underscore');
});

it('skips line comments', function () {
    $tokens = (new Lexer())->tokenize("// this is a comment\nname");

    expect($tokens)->toHaveCount(2);
    expect($tokens[0]->type)->toBe(TokenType::IDENT);
    expect($tokens[0]->value)->toBe('name');
    expect($tokens[0]->line)->toBe(2);
});

it('skips block comments across lines', function () {
    $tokens = (new Lexer())->tokenize("/* multi\nline\ncomment */ name");

    expect($tokens)->toHaveCount(2);
    expect($tokens[0]->type)->toBe(TokenType::IDENT);
    expect($tokens[0]->value)->toBe('name');
    expect($tokens[0]->line)->toBe(3);
});

it('tracks 1-based line and column on every token', function () {
    $tokens = (new Lexer())->tokenize("name \"hello\"\nrect 16 16");

    expect($tokens[0]->line)->toBe(1);
    expect($tokens[0]->col)->toBe(1);
    expect($tokens[1]->line)->toBe(1);
    expect($tokens[1]->col)->toBe(6);
    expect($tokens[2]->line)->toBe(2);
    expect($tokens[2]->col)->toBe(1);
    expect($tokens[3]->line)->toBe(2);
    expect($tokens[3]->col)->toBe(6);
    expect($tokens[4]->line)->toBe(2);
    expect($tokens[4]->col)->toBe(9);
});

it('collapses _("text") to single STRING token when i18n enabled', function () {
    $tokens = (new Lexer(translateI18nStrings: true))->tokenize('_("YES")');

    expect($tokens)->toHaveCount(2);
    expect($tokens[0]->type)->toBe(TokenType::STRING);
    expect($tokens[0]->value)->toBe('YES');
    expect($tokens[0]->isI18n)->toBeTrue();
});

it('does not collapse _("text") when i18n disabled', function () {
    $tokens = (new Lexer(translateI18nStrings: false))->tokenize('_("YES")');

    expect($tokens)->toHaveCount(5);
    expect($tokens[0]->type)->toBe(TokenType::IDENT);
    expect($tokens[0]->value)->toBe('_');
    expect($tokens[1]->type)->toBe(TokenType::LPAREN);
    expect($tokens[2]->type)->toBe(TokenType::STRING);
    expect($tokens[2]->value)->toBe('YES');
    expect($tokens[2]->isI18n)->toBeFalse();
    expect($tokens[3]->type)->toBe(TokenType::RPAREN);
});
