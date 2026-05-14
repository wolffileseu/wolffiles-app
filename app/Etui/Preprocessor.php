<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Errors\ErrorBag;
use App\Etui\Profiles\MacroDefinition;

final class Preprocessor
{
    private const MAX_INCLUDE_DEPTH = 16;
    private const MAX_SUBSTITUTION_PASSES = 8;

    /** @var array<string, string> */
    private array $defines = [];

    /** @var array<string, MacroDefinition> */
    private array $localMacros = [];

    /** @var string[] */
    private array $includeStack = [];

    /** @var list<array{original: bool, inElse: bool}> */
    private array $conditionalStack = [];

    private ErrorBag $errors;

    public function __construct(
        private readonly SourceResolver $resolver,
    ) {
        $this->errors = new ErrorBag();
    }

    /**
     * @param  array<string, string|int>  $predefined  Symbols pre-set as if by #define before processing.
     */
    public function process(string $source, array $predefined = []): PreprocessResult
    {
        $this->defines = [];
        $this->localMacros = [];
        foreach ($predefined as $name => $value) {
            $this->defines[(string) $name] = (string) $value;
        }
        $this->errors = new ErrorBag();
        $this->includeStack = [];
        $this->conditionalStack = [];

        $cleaned = $this->stripComments($source);
        $resultSource = $this->processLines(explode("\n", $cleaned));

        if ($this->conditionalStack !== []) {
            $this->errors->error('Unterminated #ifdef / #ifndef block at end of source', 0, 0);
        }

        return new PreprocessResult($resultSource, $this->localMacros, $this->errors);
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

            if (! $this->shouldEmit()) {
                $out[] = '';
                continue;
            }

            $out[] = $this->expandEvalDirectives($this->substituteDefines($line));
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

        // Conditional directives are always tracked so the stack stays balanced
        // even inside an outer dead branch — otherwise a nested #endif inside a
        // skipped #ifdef would pop the wrong frame and leak emit-state.
        if (preg_match('/^ifdef\s+(\w+)\s*$/', $body, $m)) {
            $this->conditionalStack[] = [
                'original' => array_key_exists($m[1], $this->defines),
                'inElse' => false,
            ];
            return '';
        }
        if (preg_match('/^ifndef\s+(\w+)\s*$/', $body, $m)) {
            $this->conditionalStack[] = [
                'original' => ! array_key_exists($m[1], $this->defines),
                'inElse' => false,
            ];
            return '';
        }
        if (preg_match('/^else\s*$/', $body)) {
            $top = array_pop($this->conditionalStack);
            if ($top === null) {
                $this->errors->error('#else without matching #ifdef', 0, 0);
                return '';
            }
            if ($top['inElse']) {
                $this->errors->error('Duplicate #else in same conditional', 0, 0);
            }
            $top['inElse'] = true;
            $this->conditionalStack[] = $top;
            return '';
        }
        if (preg_match('/^endif\s*$/', $body)) {
            if (array_pop($this->conditionalStack) === null) {
                $this->errors->error('#endif without matching #ifdef', 0, 0);
            }
            return '';
        }

        // Side-effecting directives below this line are gated by the
        // conditional stack so a dead branch doesn't leak its #define-s.
        if (! $this->shouldEmit()) {
            return '';
        }

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

    private function shouldEmit(): bool
    {
        foreach ($this->conditionalStack as $frame) {
            $active = $frame['inElse'] ? ! $frame['original'] : $frame['original'];
            if (! $active) {
                return false;
            }
        }
        return true;
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
        // are parsed into MacroDefinition objects and exposed via
        // PreprocessResult::$localMacros so the MacroExpander can apply them.
        // The directive itself is consumed (not emitted into source), but the
        // call sites in user code remain intact for the expander to see.
        if (isset($afterName[0]) && $afterName[0] === '(') {
            $this->parseFunctionLikeDefine($name, $afterName);
            return;
        }

        $this->defines[$name] = trim($afterName);
    }

    private function parseFunctionLikeDefine(string $name, string $afterName): void
    {
        $n = strlen($afterName);
        $depth = 0;
        $closingPos = null;
        for ($i = 0; $i < $n; $i++) {
            $ch = $afterName[$i];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    $closingPos = $i;
                    break;
                }
            }
        }
        if ($closingPos === null) {
            $this->errors->error("Unterminated parameter list in #define {$name}", 0, 0);
            return;
        }

        $paramsRaw = substr($afterName, 1, $closingPos - 1);
        $bodyTemplate = trim(substr($afterName, $closingPos + 1));

        $paramNames = [];
        foreach (explode(',', $paramsRaw) as $param) {
            $trimmed = trim($param);
            if ($trimmed !== '') {
                $paramNames[] = $trimmed;
            }
        }

        $this->localMacros[$name] = new MacroDefinition($name, $paramNames, $bodyTemplate);
    }

    /**
     * id Tech 3 build-time eval directives: $evalint(expr) / $evalfloat(expr).
     * Runs AFTER #define substitution so the symbols inside are already
     * numeric. Inner $eval directives are expanded depth-first, so
     * $evalint(2 + $evalint(3 * 4)) resolves to 14. When the inner
     * expression can't be folded (unresolved IDENT, syntax error), the
     * directive is left intact in the output and recorded in ErrorBag —
     * leaving it intact prevents an infinite re-evaluation loop and lets
     * the editor highlight the bad spot.
     */
    private function expandEvalDirectives(string $text): string
    {
        $out = '';
        $i = 0;
        $n = strlen($text);

        while ($i < $n) {
            if ($text[$i] === '$') {
                $match = $this->matchEvalDirective($text, $i);
                if ($match !== null) {
                    [$mode, $exprStart, $closingPos] = $match;
                    $rawExpr = substr($text, $exprStart, $closingPos - $exprStart);
                    $expandedExpr = $this->expandEvalDirectives($rawExpr);
                    $value = $this->evaluateExpression($expandedExpr);

                    if ($value === null) {
                        $this->errors->error(
                            "Non-constant or unparseable expression in \$eval{$mode}({$expandedExpr})",
                            0,
                            0,
                        );
                        $out .= substr($text, $i, $closingPos - $i + 1);
                    } elseif ($mode === 'int') {
                        $out .= (string) (int) $value;
                    } else {
                        $out .= (string) (float) $value;
                    }
                    $i = $closingPos + 1;
                    continue;
                }
            }
            $out .= $text[$i++];
        }

        return $out;
    }

    /**
     * @return array{0: 'int'|'float', 1: int, 2: int}|null  [mode, exprStart, closingParenPos]
     */
    private function matchEvalDirective(string $text, int $i): ?array
    {
        $n = strlen($text);
        foreach (['int' => '$evalint(', 'float' => '$evalfloat('] as $mode => $prefix) {
            $plen = strlen($prefix);
            if ($i + $plen > $n) {
                continue;
            }
            if (substr($text, $i, $plen) !== $prefix) {
                continue;
            }

            $exprStart = $i + $plen;
            $depth = 1;
            $j = $exprStart;
            while ($j < $n) {
                $ch = $text[$j];
                if ($ch === '(') {
                    $depth++;
                } elseif ($ch === ')') {
                    $depth--;
                    if ($depth === 0) {
                        return [$mode, $exprStart, $j];
                    }
                }
                $j++;
            }
            return null;
        }
        return null;
    }

    private function evaluateExpression(string $expr): int|float|null
    {
        $tokens = (new Lexer())->tokenize($expr);
        return (new ExpressionEvaluator())->evaluate($tokens);
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
