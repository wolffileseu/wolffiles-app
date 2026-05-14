<?php

declare(strict_types=1);

namespace App\Etui\Ast;

abstract class Node
{
    public function __construct(
        public readonly int $line,
    ) {}
}
