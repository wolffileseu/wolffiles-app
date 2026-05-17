<?php

namespace App\Filament\Resources\TestserverModResource\Pages;

use App\Filament\Resources\TestserverModResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTestserverMod extends EditRecord
{
    protected static string $resource = TestserverModResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
