<?php

namespace App\Filament\Resources\ClanApiKeyResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ClanApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClanApiKeys extends ListRecords
{
    protected static string $resource = ClanApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
