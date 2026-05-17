<?php

namespace App\Filament\Resources\TestserverResource\Pages;

use App\Filament\Resources\TestserverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestservers extends ListRecords
{
    protected static string $resource = TestserverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
