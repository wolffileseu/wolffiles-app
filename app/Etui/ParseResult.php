<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Ast\MacroCallNode;
use App\Etui\Ast\MenuDefNode;
use App\Etui\Errors\ErrorBag;

final class ParseResult
{
    /** @var MenuDefNode[] */
    public array $menus = [];

    /**
     * Top-level macro calls — IDENT(args) at the source root, no surrounding
     * menuDef. Real ET fixtures use this for menu-template macros like
     * QM_MENU_START(name) whose body is itself a menuDef { ... }.
     * The MacroExpander expands these in-place and pushes the resulting
     * MenuDefNodes onto $menus.
     *
     * @var MacroCallNode[]
     */
    public array $topLevelMacroCalls = [];

    public ErrorBag $errors;

    public function __construct(?ErrorBag $errors = null)
    {
        $this->errors = $errors ?? new ErrorBag();
    }
}
