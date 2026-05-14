<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Errors\ErrorBag;

final class Lexer
{
    private string $source = '';
    private int $length = 0;
    private int $pos = 0;
    private int $line = 1;
    private int $col = 1;
    private ?TokenType $lastToken = null;
    private ErrorBag $errors;

    public function __construct(
        public readonly bool $translateI18nStrings = false,
    ) {
        $this->errors = new ErrorBag();
    }

    /**
     * @return Token[]
     */
    public function tokenize(string $source, ?ErrorBag $errors = null): array
    {
        $this->source = $source;
        $this->length = strlen($source);
        $this->pos = 0;
        $this->line = 1;
        $this->col = 1;
        $this->lastToken = null;
        $this->errors = $errors ?? new ErrorBag();

        $tokens = [];

        while (true) {
            $this->skipWhitespaceAndComments();
            if ($this->isAtEnd()) {
                break;
            }

            $token = $this->scanToken();
            if ($token !== null) {
                $tokens[] = $token;
                $this->lastToken = $token->type;
            }
        }

        $tokens[] = new Token(TokenType::EOF, '', null, $this->line, $this->col);

        return $tokens;
    }

    public function errors(): ErrorBag
    {
        return $this->errors;
    }

    private function isAtEnd(): bool
    {
        return $this->pos >= $this->length;
    }

    private function peek(int $offset = 0): string
    {
        $idx = $this->pos + $offset;
        return $idx < $this->length ? $this->source[$idx] : '';
    }

    private function advance(): string
    {
        $ch = $this->source[$this->pos];
        $this->pos++;
        if ($ch === "\n") {
            $this->line++;
            $this->col = 1;
        } else {
            $this->col++;
        }
        return $ch;
    }

    private function skipWhitespaceAndComments(): void
    {
        while (! $this->isAtEnd()) {
            $ch = $this->peek();

            if ($ch === ' ' || $ch === "\t" || $ch === "\r" || $ch === "\n") {
                $this->advance();
                continue;
            }

            if ($ch === '/' && $this->peek(1) === '/') {
                while (! $this->isAtEnd() && $this->peek() !== "\n") {
                    $this->advance();
                }
                continue;
            }

            if ($ch === '/' && $this->peek(1) === '*') {
                $startLine = $this->line;
                $startCol = $this->col;
                $this->advance();
                $this->advance();
                while (! $this->isAtEnd() && ! ($this->peek() === '*' && $this->peek(1) === '/')) {
                    $this->advance();
                }
                if ($this->isAtEnd()) {
                    $this->errors->error('Unterminated block comment', $startLine, $startCol);
                    return;
                }
                $this->advance();
                $this->advance();
                continue;
            }

            return;
        }
    }

    private function scanToken(): ?Token
    {
        $line = $this->line;
        $col = $this->col;
        $ch = $this->peek();

        switch ($ch) {
            case '{':
                $this->advance();
                return new Token(TokenType::LBRACE, '{', null, $line, $col);
            case '}':
                $this->advance();
                return new Token(TokenType::RBRACE, '}', null, $line, $col);
            case '(':
                $this->advance();
                return new Token(TokenType::LPAREN, '(', null, $line, $col);
            case ')':
                $this->advance();
                return new Token(TokenType::RPAREN, ')', null, $line, $col);
            case ',':
                $this->advance();
                return new Token(TokenType::COMMA, ',', null, $line, $col);
            case '+':
                $this->advance();
                return new Token(TokenType::PLUS, '+', null, $line, $col);
            case '*':
                $this->advance();
                return new Token(TokenType::STAR, '*', null, $line, $col);
            case '/':
                $this->advance();
                return new Token(TokenType::SLASH, '/', null, $line, $col);
            case '"':
                return $this->scanString($line, $col);
        }

        if ($ch === '-') {
            if ($this->canBeUnaryMinus() && $this->numberFollowsMinus()) {
                return $this->scanNumber($line, $col);
            }
            $this->advance();
            return new Token(TokenType::MINUS, '-', null, $line, $col);
        }

        if ($this->isDigit($ch) || ($ch === '.' && $this->isDigit($this->peek(1)))) {
            return $this->scanNumber($line, $col);
        }

        if ($this->translateI18nStrings && $ch === '_' && $this->peek(1) === '(') {
            return $this->scanI18nString($line, $col);
        }

        if ($this->isIdentStart($ch)) {
            return $this->scanIdent($line, $col);
        }

        $this->errors->error("Unexpected character: '{$ch}'", $line, $col);
        $this->advance();
        return null;
    }

    private function canBeUnaryMinus(): bool
    {
        if ($this->lastToken === null) {
            return true;
        }
        if (in_array($this->lastToken, [
            TokenType::LBRACE,
            TokenType::LPAREN,
            TokenType::COMMA,
            TokenType::PLUS,
            TokenType::MINUS,
            TokenType::STAR,
            TokenType::SLASH,
        ], true)) {
            return true;
        }
        if ($this->pos > 0) {
            $prev = $this->source[$this->pos - 1];
            if ($prev === ' ' || $prev === "\t" || $prev === "\n" || $prev === "\r") {
                return true;
            }
        }
        return false;
    }

    private function numberFollowsMinus(): bool
    {
        $next = $this->peek(1);
        if ($this->isDigit($next)) {
            return true;
        }
        return $next === '.' && $this->isDigit($this->peek(2));
    }

    private function scanString(int $line, int $col): Token
    {
        $this->advance();
        $value = '';
        while (! $this->isAtEnd() && $this->peek() !== '"') {
            $value .= $this->advance();
        }
        if ($this->isAtEnd()) {
            $this->errors->error('Unterminated string literal', $line, $col);
        } else {
            $this->advance();
        }
        return new Token(TokenType::STRING, '"' . $value . '"', $value, $line, $col);
    }

    private function scanI18nString(int $line, int $col): Token
    {
        $this->advance();
        $this->advance();
        while (! $this->isAtEnd() && ($this->peek() === ' ' || $this->peek() === "\t")) {
            $this->advance();
        }
        if ($this->peek() !== '"') {
            $this->errors->error('Expected string literal after _(', $line, $col);
            return new Token(TokenType::STRING, '', '', $line, $col, isI18n: true);
        }
        $stringToken = $this->scanString($this->line, $this->col);
        while (! $this->isAtEnd() && ($this->peek() === ' ' || $this->peek() === "\t")) {
            $this->advance();
        }
        if ($this->peek() !== ')') {
            $this->errors->error('Expected closing )', $this->line, $this->col);
        } else {
            $this->advance();
        }
        return new Token(
            TokenType::STRING,
            $stringToken->lexeme,
            $stringToken->value,
            $line,
            $col,
            isI18n: true,
        );
    }

    private function scanNumber(int $line, int $col): Token
    {
        $start = $this->pos;

        if ($this->peek() === '-') {
            $this->advance();
        }

        while ($this->isDigit($this->peek())) {
            $this->advance();
        }

        if ($this->peek() === '.' && $this->isDigit($this->peek(1))) {
            $this->advance();
            while ($this->isDigit($this->peek())) {
                $this->advance();
            }
        }

        $lexeme = substr($this->source, $start, $this->pos - $start);
        $value = str_contains($lexeme, '.') ? (float) $lexeme : (int) $lexeme;

        return new Token(TokenType::NUMBER, $lexeme, $value, $line, $col);
    }

    private function scanIdent(int $line, int $col): Token
    {
        $start = $this->pos;
        while (! $this->isAtEnd() && $this->isIdentCont($this->peek())) {
            $this->advance();
        }
        $lexeme = substr($this->source, $start, $this->pos - $start);
        return new Token(TokenType::IDENT, $lexeme, $lexeme, $line, $col);
    }

    private function isDigit(string $ch): bool
    {
        return $ch >= '0' && $ch <= '9';
    }

    private function isIdentStart(string $ch): bool
    {
        return ($ch >= 'a' && $ch <= 'z')
            || ($ch >= 'A' && $ch <= 'Z')
            || $ch === '_';
    }

    private function isIdentCont(string $ch): bool
    {
        return $this->isIdentStart($ch) || $this->isDigit($ch);
    }
}
