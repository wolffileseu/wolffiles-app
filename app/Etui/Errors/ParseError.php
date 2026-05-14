<?php

declare(strict_types=1);

namespace App\Etui\Errors;

final readonly class ParseError
{
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';

    public function __construct(
        public string $message,
        public int $line,
        public int $col,
        public string $severity = self::SEVERITY_ERROR,
    ) {}
}
