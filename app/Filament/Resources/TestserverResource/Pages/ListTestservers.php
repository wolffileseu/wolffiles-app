<?php

namespace App\Filament\Resources\TestserverResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TestserverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestservers extends ListRecords
{
    protected static string $resource = TestserverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
