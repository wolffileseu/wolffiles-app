<?php

namespace App\Filament\Resources\ServerOrderResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ServerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerOrder extends EditRecord
{
    protected static string $resource = ServerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
