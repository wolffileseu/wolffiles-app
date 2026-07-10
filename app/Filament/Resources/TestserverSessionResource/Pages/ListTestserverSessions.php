<?php

namespace App\Filament\Resources\TestserverSessionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TestserverSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestserverSessions extends ListRecords
{
    protected static string $resource = TestserverSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
