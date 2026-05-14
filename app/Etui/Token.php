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
    ) {}
}
