<?php
namespace App\Filament\Resources\WikiCategoryResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\WikiCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditWikiCategory extends EditRecord
{
    protected static string $resource = WikiCategoryResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
