<?php

namespace App\Filament\Resources\TestserverResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\TestserverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestserver extends EditRecord
{
    protected static string $resource = TestserverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
