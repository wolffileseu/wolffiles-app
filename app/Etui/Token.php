<?php

declare(strict_types=1);

namespace App\Etui;

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $lexeme,
        public string|int|float|null $value,
        public int $line,
        public int $col,
        public bool $isI18n = false,
        // True when at least one whitespace or comment character preceded
        // this token in the source. The parser uses this to disambiguate
        //   BUTTON(args)   — IDENT glued to '(' is a macro call
        //   rect (val)     — IDENT followed by a space then '(' is a
        //                    property whose value happens to start with
        //                    a parenthesised expression
        public bool $precededByWhitespace = false,
    ) {}
}
