<?php

namespace App\Filament\Resources\ServerOrderResource\Pages;

use App\Filament\Resources\ServerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerOrder extends EditRecord
{
    protected static string $resource = ServerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
