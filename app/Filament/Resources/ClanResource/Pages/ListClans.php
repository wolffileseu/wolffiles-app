<?php

namespace App\Filament\Resources\ClanResource\Pages;

use App\Filament\Resources\ClanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClans extends ListRecords
{
    protected static string $resource = ClanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
