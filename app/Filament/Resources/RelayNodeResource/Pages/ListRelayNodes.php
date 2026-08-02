<?php

namespace App\Filament\Resources\RelayNodeResource\Pages;

use App\Filament\Resources\RelayNodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRelayNodes extends ListRecords
{
    protected static string $resource = RelayNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
