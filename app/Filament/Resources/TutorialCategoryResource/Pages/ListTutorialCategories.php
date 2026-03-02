<?php
namespace App\Filament\Resources\TutorialCategoryResource\Pages;
use App\Filament\Resources\TutorialCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListTutorialCategories extends ListRecords
{
    protected static string $resource = TutorialCategoryResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
