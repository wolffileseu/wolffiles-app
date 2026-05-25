<?php

namespace App\Filament\Resources\BugTracker\TaskResource\Pages;

use App\Filament\Resources\BugTracker\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;
}
