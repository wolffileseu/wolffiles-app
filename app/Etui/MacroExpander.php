<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Ast\ItemDefNode;
use App\Etui\Ast\MacroCallNode;
use App\Etui\Ast\MenuDefNode;
use App\Etui\Ast\Node;
use App\Etui\Errors\ErrorBag;
use App\Etui\Profiles\MacroDefinition;
use App\Etui\Profiles\ModProfile;

/**
 * Walks a ParseResult and replaces every MacroCallNode with the result
 * of expanding the macro's body template:
 *
 *   1. Tokenize the body template (with `##` first replaced by a
 *      sentinel IDENT that survives lexing).
 *   2. Substitute each param-name IDENT with the caller's arg
 *      Token[] from MacroCallNode->args (positional).
 *   3. Run the `##` token-paste pass: pairs of TOKEN __PASTE__ TOKEN
 *      merge into a single STRING whose value is the concatenation
 *      of the left and right operands.
 *   4. Wrap the result in a synthetic menuDef block and re-parse it
 *      with the existing Parser; splice the new children into the
 *      original parent at the call site.
 *
 * Macro source precedence is local-wins: macros declared inline in the
 * .menu file (collected by the Preprocessor as $localMacros) override
 * profile-provided macros on name collision. A file can thus customise
 * a stock BUTTON without forking the profile.
 */
final class MacroExpander
{
    /** Sentinel that survives lex/parse and triggers token-paste. */
    private const PASTE_MARKER = '__ETUI_PASTE__';

    public function __construct(
        private readonly ModProfile $profile,
    ) {}

    /**
     * @param  array<string, MacroDefinition>  $localMacros
     */
    public function expand(ParseResult $result, array $localMacros = []): ParseResult
    {
        $macros = array_merge($this->profile->macros(), $localMacros);

        foreach ($result->menus as $menu) {
            $menu->children = $this->expandChildren($menu->children, $macros, $result->errors);
        }

        return $result;
    }

    /**
     * @param  Node[]  $children
     * @param  array<string, MacroDefinition>  $macros
     * @return Node[]
     */
    private function expandChildren(array $children, array $macros, ErrorBag $errors): array
    {
        $out = [];

        foreach ($children as $child) {
            if ($child instanceof MacroCallNode) {
                foreach ($this->expandMacroCall($child, $macros, $errors) as $expanded) {
                    $out[] = $expanded;
                }
                continue;
            }

            if ($child instanceof ItemDefNode) {
                $child->children = $this->expandChildren($child->children, $macros, $errors);
            }

            $out[] = $child;
        }

        return $out;
    }

    /**
     * @param  array<string, MacroDefinition>  $macros
     * @return Node[]
     */
    private function expandMacroCall(MacroCallNode $call, array $macros, ErrorBag $errors): array
    {
        if (! isset($macros[$call->name])) {
            $errors->error(
                "Unknown macro '{$call->name}' for profile '{$this->profile->slug()}'",
                $call->line,
                0,
            );
            return [];
        }

        $def = $macros[$call->name];

        if (count($call->args) !== count($def->paramNames)) {
            $errors->error(
                "Macro '{$call->name}' expects ".count($def->paramNames).' args, got '.count($call->args),
                $call->line,
                0,
            );
            return [];
        }

        $argMap = array_combine($def->paramNames, $call->args);

        $bodyTokens = $this->lexBodyTemplate($def->bodyTemplate);
        $substituted = $this->substituteParams($bodyTokens, $argMap);
        $pasted = $this->applyPasteOperator($substituted);

        return $this->reparseAsChildren($pasted, $errors);
    }

    /**
     * Replace `##` with a sentinel IDENT before lexing so the `#`
     * character — otherwise rejected by the Lexer — survives until the
     * post-substitution paste pass can act on it.
     *
     * @return Token[]
     */
    private function lexBodyTemplate(string $template): array
    {
        $marked = str_replace('##', ' '.self::PASTE_MARKER.' ', $template);
        return (new Lexer($this->profile->supportsI18nWrapper()))->tokenize($marked);
    }

    /**
     * @param  Token[]  $bodyTokens
     * @param  array<string, Token[]>  $argMap
     * @return Token[]
     */
    private function substituteParams(array $bodyTokens, array $argMap): array
    {
        $out = [];
        foreach ($bodyTokens as $tok) {
            if ($tok->type === TokenType::IDENT && isset($argMap[$tok->lexeme])) {
                // Re-stamp arg tokens with the slot's line/col. The Parser's
                // "property runs to end of line" rule is line-based, and arg
                // tokens carry their original call-site lines — naively
                // splicing them in would mid-property cause a spurious line
                // change and chop the property values list short.
                foreach ($argMap[$tok->lexeme] as $argTok) {
                    $out[] = new Token(
                        $argTok->type,
                        $argTok->lexeme,
                        $argTok->value,
                        $tok->line,
                        $tok->col,
                        $argTok->isI18n,
                    );
                }
                continue;
            }
            $out[] = $tok;
        }
        return $out;
    }

    /**
     * C-preprocessor `##` token-paste, restricted here to the common
     * STRING/IDENT/NUMBER pairs that menumacros.h actually uses. Both
     * operands' stringified values are concatenated; the result is
     * always a STRING (matches how the game engine ends up using these
     * for item-name cross-references like
     *   setitemcolor "bttn"##BUTTON_TEXT forecolor ...).
     *
     * @param  Token[]  $tokens
     * @return Token[]
     */
    private function applyPasteOperator(array $tokens): array
    {
        $out = [];
        $n = count($tokens);
        $i = 0;

        while ($i < $n) {
            $tok = $tokens[$i];

            $isPasteMarker = $tok->type === TokenType::IDENT
                && $tok->lexeme === self::PASTE_MARKER;

            if ($isPasteMarker && $out !== [] && $i + 1 < $n) {
                $left = array_pop($out);
                $right = $tokens[$i + 1];
                $i += 2;

                $leftVal = $this->stringifyForPaste($left);
                $rightVal = $this->stringifyForPaste($right);
                $merged = $leftVal.$rightVal;

                $out[] = new Token(
                    TokenType::STRING,
                    '"'.$merged.'"',
                    $merged,
                    $left->line,
                    $left->col,
                );
                continue;
            }

            // Drop a stray paste marker (no left or right neighbour) — never
            // happens in practice but keeps the output clean if it ever does.
            if ($isPasteMarker) {
                $i++;
                continue;
            }

            $out[] = $tok;
            $i++;
        }

        return $out;
    }

    private function stringifyForPaste(Token $t): string
    {
        return match (true) {
            $t->type === TokenType::STRING => (string) $t->value,
            $t->type === TokenType::IDENT => (string) $t->value,
            $t->type === TokenType::NUMBER => (string) $t->value,
            default => $t->lexeme,
        };
    }

    /**
     * Wrap the post-paste token stream in a synthetic `menuDef { ... }`
     * and re-parse it so the body parses as ordinary body statements
     * (properties, itemDefs, event blocks). Take the synthetic menu's
     * children as the expansion result. Mini-parse errors are forwarded
     * to the outer ErrorBag with a hint so the caller can tell they
     * came from inside a macro body.
     *
     * @param  Token[]  $tokens
     * @return Node[]
     */
    private function reparseAsChildren(array $tokens, ErrorBag $outerErrors): array
    {
        $tokens = array_values(array_filter(
            $tokens,
            static fn (Token $t): bool => $t->type !== TokenType::EOF,
        ));

        $line = $tokens === [] ? 0 : $tokens[0]->line;

        $wrapped = array_merge(
            [
                new Token(TokenType::IDENT, 'menuDef', 'menuDef', $line, 1),
                new Token(TokenType::LBRACE, '{', null, $line, 1),
            ],
            $tokens,
            [
                new Token(TokenType::RBRACE, '}', null, $line, 1),
                new Token(TokenType::EOF, '', null, $line, 1),
            ],
        );

        $mini = (new Parser())->parse($wrapped);

        foreach ($mini->errors->all() as $e) {
            $outerErrors->error('(in macro body) '.$e->message, $e->line, $e->col);
        }

        if ($mini->menus === []) {
            return [];
        }

        return $mini->menus[0]->children;
    }
}
