<?php

declare(strict_types=1);

namespace App\Etui\Errors;

final class ErrorBag
{
    /** @var ParseError[] */
    private array $errors = [];

    public function error(string $message, int $line, int $col): void
    {
        $this->errors[] = new ParseError($message, $line, $col, ParseError::SEVERITY_ERROR);
    }

    public function warning(string $message, int $line, int $col): void
    {
        $this->errors[] = new ParseError($message, $line, $col, ParseError::SEVERITY_WARNING);
    }

    public function isEmpty(): bool
    {
        return $this->errors === [];
    }

    public function hasErrors(): bool
    {
        foreach ($this->errors as $e) {
            if ($e->severity === ParseError::SEVERITY_ERROR) {
                return true;
            }
        }
        return false;
    }

    /** @return ParseError[] */
    public function all(): array
    {
        return $this->errors;
    }

    public function count(): int
    {
        return count($this->errors);
    }
}
