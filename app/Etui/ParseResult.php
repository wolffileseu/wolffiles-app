<?php

declare(strict_types=1);

namespace App\Etui;

use App\Etui\Ast\MenuDefNode;
use App\Etui\Errors\ErrorBag;

final class ParseResult
{
    /** @var MenuDefNode[] */
    public array $menus = [];

    public ErrorBag $errors;

    public function __construct(?ErrorBag $errors = null)
    {
        $this->errors = $errors ?? new ErrorBag();
    }
}
