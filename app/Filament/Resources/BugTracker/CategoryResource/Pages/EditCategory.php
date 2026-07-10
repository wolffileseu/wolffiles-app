<?php

namespace App\Filament\Resources\BugTracker\CategoryResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BugTracker\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
