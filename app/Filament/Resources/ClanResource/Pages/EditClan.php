<?php

namespace App\Filament\Resources\ClanResource\Pages;

use App\Filament\Resources\ClanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClan extends EditRecord
{
    protected static string $resource = ClanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
