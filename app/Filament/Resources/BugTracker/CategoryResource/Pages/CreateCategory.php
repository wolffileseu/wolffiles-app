<?php

namespace App\Filament\Resources\BugTracker\CategoryResource\Pages;

use App\Filament\Resources\BugTracker\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
