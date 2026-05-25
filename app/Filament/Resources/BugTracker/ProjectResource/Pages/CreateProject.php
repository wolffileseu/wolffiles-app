<?php

namespace App\Filament\Resources\BugTracker\ProjectResource\Pages;

use App\Filament\Resources\BugTracker\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;
}
