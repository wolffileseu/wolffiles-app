<?php

declare(strict_types=1);

namespace App\Etui\Ast;

use App\Etui\Token;

final class EventBlockNode extends Node
{
    /**
     * @param  Token[]  $body  Raw, brace-balanced tokens between the `{` and `}` of the event block.
     *                          Intentionally NOT further parsed — UI-script statement semantics
     *                          (close, open, play, uiScript, setitemcolor, fadein, ...) are the
     *                          renderer's responsibility in Phase 4.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $body,
        int $line,
    ) {
        parent::__construct($line);
    }
}
