<?php

declare(strict_types=1);

use App\Etui\ExpressionEvaluator;
use App\Etui\Lexer;

function evalExpr(string $expr): int|float|null
{
    $tokens = (new Lexer())->tokenize($expr);
    return (new ExpressionEvaluator())->evaluate($tokens);
}

it('evaluates a single integer literal', function () {
    expect(evalExpr('42'))->toBe(42);
});

it('respects operator precedence (* before +)', function () {
    expect(evalExpr('2 + 3 * 4'))->toBe(14);
});

it('handles parenthesised float arithmetic to full precision', function () {
    // The reference scenario from the menumacros.h SUBWINDOW_X define:
    // .5 * (640 - 256) should be 192.0 throughout the float pipeline.
    // Caller decides truncation (e.g. $evalint applies (int) at the end).
    expect(evalExpr('0.5 * (640 - 256)'))->toBe(192.0);
});

it('returns null on non-constant identifier so Preprocessor can flag it', function () {
    expect(evalExpr('WINDOW_WIDTH + 1'))->toBeNull();
});

it('returns null on division by zero', function () {
    expect(evalExpr('5 / 0'))->toBeNull();
});
