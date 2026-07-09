<?php

namespace App\Filament\Resources\BugTracker\ProjectResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BugTracker\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
