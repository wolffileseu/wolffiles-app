<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Errors\ErrorBag;

final class Preprocessor
{
    private const MAX_INCLUDE_DEPTH = 16;
    private const MAX_SUBSTITUTION_PASSES = 8;

    /** @var array<string, string> */
    private array $defines = [];

    /** @var string[] */
    private array $includeStack = [];

    private ErrorBag $errors;

    public function __construct(
        private readonly SourceResolver $resolver,
    ) {
        $this->errors = new ErrorBag();
    }

    /**
     * @param  array<string, string|int>  $predefined  Symbols pre-set as if by #define before processing.
     */
    public function process(string $source, array $predefined = []): string
    {
        $this->defines = [];
        foreach ($predefined as $name => $value) {
            $this->defines[(string) $name] = (string) $value;
        }
        $this->errors = new ErrorBag();
        $this->includeStack = [];

        $cleaned = $this->stripComments($source);
        return $this->processLines(explode("\n", $cleaned));
    }

    public function errors(): ErrorBag
    {
        return $this->errors;
    }

    /**
     * @param  string[]  $lines
     */
    private function processLines(array $lines): string
    {
        $out = [];
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];

            // Backslash-newline continuation joins multi-line directives so the
            // function-like #define body in menumacros.h is consumed as one line.
            while ($this->lineContinues($line) && $i + 1 < $count) {
                $line = substr(rtrim($line, "\r"), 0, -1) . ' ' . $lines[++$i];
            }

            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '#')) {
                $out[] = $this->handleDirective($trimmed);
                continue;
            }

            $out[] = $this->substituteDefines($line);
        }

        return implode("\n", $out);
    }

    private function lineContinues(string $line): bool
    {
        $stripped = rtrim($line, "\r");
        return $stripped !== '' && substr($stripped, -1) === '\\';
    }

    private function handleDirective(string $line): string
    {
        // Strip leading # and any spacing.
        $body = ltrim(substr($line, 1));

        if (preg_match('/^include\s+"([^"]+)"\s*$/', $body, $m)) {
            return $this->handleInclude($m[1]);
        }

        if (preg_match('/^define\s+(\w+)/', $body, $m)) {
            $this->handleDefine($body, $m[1]);
            return '';
        }

        if (preg_match('/^undef\s+(\w+)\s*$/', $body, $m)) {
            unset($this->defines[$m[1]]);
            return '';
        }

        // Unknown directives are silently dropped in Phase 1 — the editor's
        // live-linter can flag them at a higher layer once we know which
        // directives ET mods rely on beyond the documented set.
        return '';
    }

    private function handleInclude(string $path): string
    {
        if (count($this->includeStack) >= self::MAX_INCLUDE_DEPTH) {
            $this->errors->error("Max #include depth ({$this->maxIncludeDepth()}) reached at {$path}", 0, 0);
            return '';
        }

        $content = $this->resolver->resolve($path);
        if ($content === null) {
            $this->errors->error("Cannot resolve #include: {$path}", 0, 0);
            return '';
        }

        $this->includeStack[] = $path;
        $cleaned = $this->stripComments($content);
        $result = $this->processLines(explode("\n", $cleaned));
        array_pop($this->includeStack);

        return $result;
    }

    private function maxIncludeDepth(): int
    {
        return self::MAX_INCLUDE_DEPTH;
    }

    private function handleDefine(string $body, string $name): void
    {
        $afterDirective = ltrim(substr($body, strlen('define')));
        $afterName = substr($afterDirective, strlen($name));

        // Function-like macros — NAME(params) with NO space between NAME and '(' —
        // are the territory of the MacroExpander, not the preprocessor. We strip
        // the directive so #include "ui/menumacros.h" doesn't blow up downstream,
        // while leaving the call sites in user .menu files intact for expansion.
        if (isset($afterName[0]) && $afterName[0] === '(') {
            return;
        }

        $this->defines[$name] = trim($afterName);
    }

    private function substituteDefines(string $text): string
    {
        if ($this->defines === []) {
            return $text;
        }

        for ($pass = 0; $pass < self::MAX_SUBSTITUTION_PASSES; $pass++) {
            $changed = false;
            $next = preg_replace_callback(
                '/\b([A-Za-z_]\w*)\b/',
                function (array $m) use (&$changed): string {
                    if (! array_key_exists($m[1], $this->defines)) {
                        return $m[0];
                    }
                    $replacement = $this->defines[$m[1]];
                    if ($replacement === $m[0]) {
                        return $m[0];
                    }
                    $changed = true;
                    return $replacement;
                },
                $text,
            );

            if (! $changed || $next === null) {
                return $text;
            }
            $text = $next;
        }

        return $text;
    }

    /**
     * Strip line + block comments while preserving line and column
     * positions (replace comment characters with spaces, keep newlines).
     * Strings are skipped so `"http://..."` is not mistaken for a line comment.
     */
    private function stripComments(string $src): string
    {
        $out = '';
        $i = 0;
        $n = strlen($src);

        while ($i < $n) {
            $ch = $src[$i];

            if ($ch === '"') {
                $out .= $ch;
                $i++;
                while ($i < $n && $src[$i] !== '"') {
                    $out .= $src[$i++];
                }
                if ($i < $n) {
                    $out .= $src[$i++];
                }
                continue;
            }

            if ($ch === '/' && $i + 1 < $n && $src[$i + 1] === '/') {
                while ($i < $n && $src[$i] !== "\n") {
                    $out .= ' ';
                    $i++;
                }
                continue;
            }

            if ($ch === '/' && $i + 1 < $n && $src[$i + 1] === '*') {
                $out .= '  ';
                $i += 2;
                while ($i < $n && ! ($src[$i] === '*' && $i + 1 < $n && $src[$i + 1] === '/')) {
                    $out .= ($src[$i] === "\n") ? "\n" : ' ';
                    $i++;
                }
                if ($i < $n) {
                    $out .= '  ';
                    $i += 2;
                }
                continue;
            }

            $out .= $ch;
            $i++;
        }

        return $out;
    }
}
