<?php

namespace App\Filament\Resources\ServerProductResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ServerProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerProduct extends EditRecord
{
    protected static string $resource = ServerProductResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
