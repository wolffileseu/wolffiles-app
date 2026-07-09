<?php
namespace App\Filament\Resources\MissingTextureResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\MissingTextureResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditMissingTexture extends EditRecord
{
    protected static string $resource = MissingTextureResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
