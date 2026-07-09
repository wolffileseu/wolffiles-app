<?php
namespace App\Filament\Resources\TutorialCategoryResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\TutorialCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditTutorialCategory extends EditRecord
{
    protected static string $resource = TutorialCategoryResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
