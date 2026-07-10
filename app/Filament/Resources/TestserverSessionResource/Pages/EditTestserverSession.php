<?php

namespace App\Filament\Resources\TestserverSessionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\TestserverSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestserverSession extends EditRecord
{
    protected static string $resource = TestserverSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
