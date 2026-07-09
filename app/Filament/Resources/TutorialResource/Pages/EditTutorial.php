<?php
namespace App\Filament\Resources\TutorialResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\TutorialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditTutorial extends EditRecord
{
    protected static string $resource = TutorialResource::class;
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
