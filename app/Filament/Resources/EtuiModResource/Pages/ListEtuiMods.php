<?php

declare(strict_types=1);

namespace App\Filament\Resources\EtuiModResource\Pages;

use App\Filament\Resources\EtuiModResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEtuiMods extends ListRecords
{
    protected static string $resource = EtuiModResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
