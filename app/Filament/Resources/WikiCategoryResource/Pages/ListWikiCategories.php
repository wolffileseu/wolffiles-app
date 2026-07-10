<?php
namespace App\Filament\Resources\WikiCategoryResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\WikiCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListWikiCategories extends ListRecords
{
    protected static string $resource = WikiCategoryResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
