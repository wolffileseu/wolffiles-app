<?php

declare(strict_types=1);

namespace App\Filament\Resources\EtuiProjectResource\Pages;

use App\Filament\Resources\EtuiProjectResource;
use Filament\Resources\Pages\ListRecords;

class ListEtuiProjects extends ListRecords
{
    protected static string $resource = EtuiProjectResource::class;

    // No "New project" header action — projects are user-managed via Phase 3
    // routes, not from the admin panel.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
