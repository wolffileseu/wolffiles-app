<?php

namespace App\Filament\Resources\ForumCategoryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ForumCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForumCategories extends ListRecords
{
    protected static string $resource = ForumCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Neue Kategorie'),
        ];
    }
}
