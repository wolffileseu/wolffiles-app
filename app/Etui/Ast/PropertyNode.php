<?php

declare(strict_types=1);

namespace App\Etui\Ast;

use App\Etui\Token;

final class PropertyNode extends Node
{
    /**
     * @param  Token[]  $values  Empty array for flag-style properties (e.g. `popup`, `decoration`).
     *                            Values are the raw token sequence between the property name and the
     *                            next line — arithmetic like `128-12` survives as-is so a later
     *                            evaluator can fold it with profile context.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $values,
        int $line,
    ) {
        parent::__construct($line);
    }
}
