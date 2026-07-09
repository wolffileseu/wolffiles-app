<?php

namespace App\Filament\Resources\ClanApiKeyResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ClanApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClanApiKey extends EditRecord
{
    protected static string $resource = ClanApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
