<?php

declare(strict_types=1);

namespace App\Etui\Ast;

use App\Etui\Token;

final class MacroCallNode extends Node
{
    /**
     * @param  Token[][]  $args  Comma-split (at paren-depth 1) raw token sequences. Semicolons
     *                            within an arg pass through verbatim — the MacroExpander
     *                            decides what to do with them per macro definition.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $args,
        int $line,
    ) {
        parent::__construct($line);
    }
}
