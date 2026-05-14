<?php

declare(strict_types=1);

namespace App\Etui\Ast;

final class ItemDefNode extends Node
{
    /**
     * Heterogeneous children preserve source order — important for properties
     * with override-by-later-occurrence semantics and for event blocks whose
     * relative position to properties may matter to the renderer.
     *
     * @var Node[]
     */
    public array $children = [];
}
