<?php

declare(strict_types=1);

namespace App\Etui;

/**
 * Folds constant arithmetic over a Token[] stream from the Lexer.
 *
 * Grammar (precedence-climbed by hand):
 *   expression := term  (('+' | '-') term)*
 *   term       := factor (('*' | '/') factor)*
 *   factor     := NUMBER | '(' expression ')' | IDENT
 *
 * Returns int|float on success, null when the expression contains an
 * unresolved IDENT, has a syntax error, or trips a runtime guard like
 * division-by-zero. Callers cast the result (Preprocessor's $evalint
 * truncates with (int); $evalfloat keeps it as (float)).
 */
final class ExpressionEvaluator
{
    /** @var Token[] */
    private array $tokens = [];

    private int $pos = 0;

    /**
     * @param  Token[]  $tokens
     */
    public function evaluate(array $tokens): int|float|null
    {
        $this->tokens = array_values(array_filter(
            $tokens,
            static fn (Token $t): bool => $t->type !== TokenType::EOF,
        ));
        $this->pos = 0;

        if ($this->tokens === []) {
            return null;
        }

        $result = $this->expression();
        if ($result === null) {
            return null;
        }
        // Trailing tokens mean we didn't consume the whole expression — bail.
        if ($this->pos < count($this->tokens)) {
            return null;
        }
        return $result;
    }

    private function expression(): int|float|null
    {
        $left = $this->term();
        if ($left === null) {
            return null;
        }
        while (($t = $this->peek()) !== null
            && ($t->type === TokenType::PLUS || $t->type === TokenType::MINUS)
        ) {
            $op = $this->advance()->type;
            $right = $this->term();
            if ($right === null) {
                return null;
            }
            $left = $op === TokenType::PLUS ? $left + $right : $left - $right;
        }
        return $left;
    }

    private function term(): int|float|null
    {
        $left = $this->factor();
        if ($left === null) {
            return null;
        }
        while (($t = $this->peek()) !== null
            && ($t->type === TokenType::STAR || $t->type === TokenType::SLASH)
        ) {
            $op = $this->advance()->type;
            $right = $this->factor();
            if ($right === null) {
                return null;
            }
            if ($op === TokenType::SLASH && $right == 0) {
                return null;
            }
            $left = $op === TokenType::STAR ? $left * $right : $left / $right;
        }
        return $left;
    }

    private function factor(): int|float|null
    {
        $t = $this->peek();
        if ($t === null) {
            return null;
        }

        if ($t->type === TokenType::LPAREN) {
            $this->advance();
            $inner = $this->expression();
            if ($inner === null) {
                return null;
            }
            $closing = $this->peek();
            if ($closing === null || $closing->type !== TokenType::RPAREN) {
                return null;
            }
            $this->advance();
            return $inner;
        }

        if ($t->type === TokenType::NUMBER) {
            $this->advance();
            return is_int($t->value) || is_float($t->value) ? $t->value : null;
        }

        // IDENT, STRING, anything else — non-constant for Phase 1.
        return null;
    }

    private function peek(): ?Token
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function advance(): Token
    {
        return $this->tokens[$this->pos++];
    }
}
