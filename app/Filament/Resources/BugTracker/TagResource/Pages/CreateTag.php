<?php

namespace App\Filament\Resources\BugTracker\TagResource\Pages;

use App\Filament\Resources\BugTracker\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;
}
