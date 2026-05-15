<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Ast\EventBlockNode;
use App\Etui\Ast\ItemDefNode;
use App\Etui\Ast\MacroCallNode;
use App\Etui\Ast\MenuDefNode;
use App\Etui\Ast\Node;
use App\Etui\Ast\PropertyNode;
use App\Etui\Errors\ParseError;

/**
 * Turns a ParseResult into a JSON-friendly nested array for the
 * front-end renderer and the /api/etui/parse endpoint. Phase-1 domain
 * classes intentionally don't implement JsonSerializable themselves —
 * keeping them pure means the same AST can drive a future GraphQL
 * surface, a CLI dumper, etc. without dragging serialisation rules
 * around in the domain layer.
 *
 * Shape:
 *   {
 *     menus: [
 *       { kind: 'menu', line, children: [
 *         { kind: 'property', line, name, values: [ {type,value,lexeme,line,col}, ... ] },
 *         { kind: 'item',     line, children: [...] },
 *         { kind: 'event',    line, name, body: [...tokens...] },
 *         { kind: 'macro',    line, name, args: [[...tokens...], ...] }
 *       ]}, ...
 *     ],
 *     errors: [ {line, col, severity, message}, ... ]
 *   }
 */
final class AstSerializer
{
    /**
     * @return array{menus: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>}
     */
    public static function serialize(ParseResult $result): array
    {
        return [
            'menus' => array_map([self::class, 'serializeNode'], $result->menus),
            'errors' => array_values(array_map(
                static fn (ParseError $e): array => [
                    'line' => $e->line,
                    'col' => $e->col,
                    'severity' => $e->severity,
                    'message' => $e->message,
                ],
                $result->errors->all(),
            )),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializeNode(Node $node): array
    {
        if ($node instanceof MenuDefNode) {
            return [
                'kind' => 'menu',
                'line' => $node->line,
                'children' => array_map([self::class, 'serializeNode'], $node->children),
            ];
        }
        if ($node instanceof ItemDefNode) {
            return [
                'kind' => 'item',
                'line' => $node->line,
                'children' => array_map([self::class, 'serializeNode'], $node->children),
            ];
        }
        if ($node instanceof PropertyNode) {
            return [
                'kind' => 'property',
                'line' => $node->line,
                'name' => $node->name,
                'values' => array_map([self::class, 'serializeToken'], $node->values),
            ];
        }
        if ($node instanceof EventBlockNode) {
            return [
                'kind' => 'event',
                'line' => $node->line,
                'name' => $node->name,
                'body' => array_map([self::class, 'serializeToken'], $node->body),
            ];
        }
        if ($node instanceof MacroCallNode) {
            return [
                'kind' => 'macro',
                'line' => $node->line,
                'name' => $node->name,
                'args' => array_map(
                    static fn (array $argTokens): array => array_map(
                        [self::class, 'serializeToken'],
                        $argTokens,
                    ),
                    $node->args,
                ),
            ];
        }

        return ['kind' => 'unknown', 'line' => $node->line];
    }

    /**
     * @return array<string, mixed>
     */
    private static function serializeToken(Token $t): array
    {
        return [
            'type' => $t->type->value,
            'value' => $t->value,
            'lexeme' => $t->lexeme,
            'line' => $t->line,
            'col' => $t->col,
        ];
    }
}
