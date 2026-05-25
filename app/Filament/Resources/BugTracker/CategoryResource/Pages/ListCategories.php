<?php

namespace App\Filament\Resources\BugTracker\CategoryResource\Pages;

use App\Filament\Resources\BugTracker\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
