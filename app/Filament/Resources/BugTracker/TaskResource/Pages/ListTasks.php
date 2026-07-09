<?php

namespace App\Filament\Resources\BugTracker\TaskResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\BugTracker\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
