<?php
namespace App\Filament\Resources\TutorialResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\TutorialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListTutorials extends ListRecords
{
    protected static string $resource = TutorialResource::class;
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
