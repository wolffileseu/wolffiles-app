<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Ast\EventBlockNode;
use App\Etui\Ast\ItemDefNode;
use App\Etui\Ast\MacroCallNode;
use App\Etui\Ast\MenuDefNode;
use App\Etui\Ast\Node;
use App\Etui\Ast\PropertyNode;
use App\Etui\Errors\ErrorBag;

/**
 * Recursive-descent parser over the Lexer's Token[] stream.
 *
 * Strategy: error-tolerant — every failure records a ParseError in the
 * ErrorBag and recovers to a synchronisation point (next menuDef / next
 * RBRACE), so the editor's live-linter gets every problem in one pass
 * rather than a single fatal exception.
 *
 * The single decision the parser makes per statement inside a menuDef or
 * itemDef body is based on the token AFTER the leading identifier:
 *   - itemDef + LBRACE  -> nested ItemDefNode
 *   - any IDENT + LBRACE -> EventBlockNode (raw body)
 *   - any IDENT + LPAREN -> MacroCallNode (raw, comma-split args)
 *   - otherwise          -> PropertyNode (values until next line / RBRACE)
 */
final class Parser
{
    /** @var Token[] */
    private array $tokens = [];

    private int $pos = 0;

    private ErrorBag $errors;

    public function __construct()
    {
        $this->errors = new ErrorBag();
    }

    /**
     * @param  Token[]  $tokens
     */
    public function parse(array $tokens): ParseResult
    {
        $this->tokens = $tokens;
        $this->pos = 0;
        $result = new ParseResult();
        $this->errors = $result->errors;

        // Recognised top-level keywords. menuDef is the common case; ETMain's
        // global.menu opens with assetGlobalDef instead, declaring shared
        // fonts/cursors that subsequent menus reference. Both have the same
        // shape (`IDENT { ... }`) and same body grammar.
        $topLevelKeywords = ['menuDef', 'assetGlobalDef'];

        while (! $this->isAtEnd()) {
            $t = $this->peek();
            if ($t === null || $t->type === TokenType::EOF) {
                break;
            }

            if ($t->type === TokenType::IDENT && in_array($t->value, $topLevelKeywords, true)) {
                $result->menus[] = $this->parseMenuDef();
                continue;
            }

            // Top-level macro call: IDENT glued to '(' at the source root.
            // The macro's body is itself a menuDef { ... }; the expander
            // turns this into a real top-level MenuDefNode.
            $next = $this->peekAt(1);
            if ($t->type === TokenType::IDENT
                && $next?->type === TokenType::LPAREN
                && ! $next->precededByWhitespace
            ) {
                $result->topLevelMacroCalls[] = $this->parseMacroCall();
                continue;
            }

            $this->errors->error(
                "Unexpected token '{$t->lexeme}' at top level — expected one of: ".implode(', ', $topLevelKeywords),
                $t->line,
                $t->col,
            );
            $this->synchronizeToTopLevel();
        }

        return $result;
    }

    public function errors(): ErrorBag
    {
        return $this->errors;
    }

    private function parseMenuDef(): MenuDefNode
    {
        $startTok = $this->advance();
        $node = new MenuDefNode($startTok->line);

        if (! $this->expect(TokenType::LBRACE, "Expected '{' after 'menuDef'", $startTok)) {
            return $node;
        }

        $this->parseBlockBody($node, sentinelKind: 'menuDef', sentinelLine: $startTok->line);

        return $node;
    }

    private function parseItemDef(): ItemDefNode
    {
        $startTok = $this->advance();
        $node = new ItemDefNode($startTok->line);

        if (! $this->expect(TokenType::LBRACE, "Expected '{' after 'itemDef'", $startTok)) {
            return $node;
        }

        $this->parseBlockBody($node, sentinelKind: 'itemDef', sentinelLine: $startTok->line);

        return $node;
    }

    private function parseBlockBody(MenuDefNode|ItemDefNode $parent, string $sentinelKind, int $sentinelLine): void
    {
        while (! $this->isAtEnd()) {
            $t = $this->peek();
            if ($t === null || $t->type === TokenType::EOF) {
                $this->errors->error(
                    "Unterminated {$sentinelKind} block",
                    $sentinelLine,
                    0,
                );
                return;
            }
            if ($t->type === TokenType::RBRACE) {
                $this->advance();
                return;
            }

            $child = $this->parseBodyStatement();
            if ($child !== null) {
                $parent->children[] = $child;
            }
        }
    }

    private function parseBodyStatement(): ?Node
    {
        $t = $this->peek();
        if ($t === null || $t->type === TokenType::EOF) {
            return null;
        }

        if ($t->type !== TokenType::IDENT) {
            $this->errors->error(
                "Expected identifier, got '{$t->lexeme}'",
                $t->line,
                $t->col,
            );
            $this->advance();
            return null;
        }

        $next = $this->peekAt(1);

        if ($t->value === 'itemDef' && $next?->type === TokenType::LBRACE) {
            return $this->parseItemDef();
        }
        if ($next?->type === TokenType::LBRACE) {
            return $this->parseEventBlock();
        }
        // Macro call disambiguation: `BUTTON(args)` has no space between the
        // identifier and '(', `rect (expr)` has a space. The latter is a
        // property whose first value is a parenthesised expression — without
        // this check, every $eval-stripped body's `rect (X) (Y) ...` would
        // be misread as a macro call into a nonexistent `rect` macro.
        if ($next?->type === TokenType::LPAREN && ! $next->precededByWhitespace) {
            return $this->parseMacroCall();
        }
        return $this->parseProperty();
    }

    private function parseProperty(): PropertyNode
    {
        $nameTok = $this->advance();
        $values = [];

        while (! $this->isAtEnd()) {
            $t = $this->peek();
            if ($t === null || $t->type === TokenType::EOF) {
                break;
            }
            if ($t->type === TokenType::RBRACE) {
                break;
            }
            // Property goes to end-of-line: next token on a later line is a
            // new statement. The Lexer's line metadata on every token is the
            // only thing this check needs — no NEWLINE token noise.
            if ($t->line > $nameTok->line) {
                break;
            }
            $values[] = $this->advance();
        }

        return new PropertyNode($nameTok->value, $values, $nameTok->line);
    }

    private function parseEventBlock(): EventBlockNode
    {
        $nameTok = $this->advance();
        $this->advance(); // consume LBRACE

        $body = [];
        $depth = 1;

        while (! $this->isAtEnd() && $depth > 0) {
            $t = $this->peek();
            if ($t === null || $t->type === TokenType::EOF) {
                $this->errors->error(
                    "Unterminated event block '{$nameTok->value}'",
                    $nameTok->line,
                    $nameTok->col,
                );
                break;
            }
            if ($t->type === TokenType::LBRACE) {
                $depth++;
                $body[] = $this->advance();
                continue;
            }
            if ($t->type === TokenType::RBRACE) {
                $depth--;
                if ($depth === 0) {
                    $this->advance();
                    break;
                }
                $body[] = $this->advance();
                continue;
            }
            $body[] = $this->advance();
        }

        return new EventBlockNode($nameTok->value, $body, $nameTok->line);
    }

    private function parseMacroCall(): MacroCallNode
    {
        $nameTok = $this->advance();
        $this->advance(); // consume LPAREN

        $args = [];
        $current = [];
        $depth = 1;
        $sawAnyToken = false;

        while (! $this->isAtEnd() && $depth > 0) {
            $t = $this->peek();
            if ($t === null || $t->type === TokenType::EOF) {
                $this->errors->error(
                    "Unterminated macro call '{$nameTok->value}'",
                    $nameTok->line,
                    $nameTok->col,
                );
                break;
            }
            if ($t->type === TokenType::LPAREN) {
                $depth++;
                $current[] = $this->advance();
                $sawAnyToken = true;
                continue;
            }
            if ($t->type === TokenType::RPAREN) {
                $depth--;
                if ($depth === 0) {
                    $this->advance();
                    if ($sawAnyToken) {
                        $args[] = $current;
                    }
                    break;
                }
                $current[] = $this->advance();
                continue;
            }
            if ($t->type === TokenType::COMMA && $depth === 1) {
                $args[] = $current;
                $current = [];
                $this->advance();
                $sawAnyToken = true;
                continue;
            }
            $current[] = $this->advance();
            $sawAnyToken = true;
        }

        return new MacroCallNode($nameTok->value, $args, $nameTok->line);
    }

    private function expect(TokenType $type, string $message, Token $context): bool
    {
        $t = $this->peek();
        if ($t === null || $t->type !== $type) {
            $this->errors->error(
                $message,
                $context->line,
                $context->col,
            );
            return false;
        }
        $this->advance();
        return true;
    }

    private function synchronizeToTopLevel(): void
    {
        while (! $this->isAtEnd()) {
            $t = $this->peek();
            if ($t === null || $t->type === TokenType::EOF) {
                return;
            }
            if ($t->type === TokenType::IDENT && ($t->value === 'menuDef' || $t->value === 'assetGlobalDef')) {
                return;
            }
            $this->advance();
        }
    }

    private function peek(): ?Token
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function peekAt(int $offset): ?Token
    {
        return $this->tokens[$this->pos + $offset] ?? null;
    }

    private function advance(): Token
    {
        return $this->tokens[$this->pos++];
    }

    private function isAtEnd(): bool
    {
        $t = $this->peek();
        return $t === null || $t->type === TokenType::EOF;
    }
}
