<?php

namespace App\Filament\Resources\RelayNodeResource\Pages;

use App\Filament\Resources\RelayNodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRelayNode extends EditRecord
{
    protected static string $resource = RelayNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
