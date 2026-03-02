<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        /** @phpstan-ignore argument.type */
        /** @phpstan-ignore argument.type */
        return view('layouts.app');
    }
}
