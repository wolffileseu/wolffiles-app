<?php

namespace App\Filament\Resources\TestserverModResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TestserverModResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestserverMods extends ListRecords
{
    protected static string $resource = TestserverModResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
