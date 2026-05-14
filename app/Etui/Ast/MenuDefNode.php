<?php

declare(strict_types=1);

namespace App\Etui\Ast;

final class MenuDefNode extends Node
{
    /** @var Node[] */
    public array $children = [];
}
