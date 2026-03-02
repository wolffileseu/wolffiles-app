<?php

namespace App\Filament\Resources\ServerOrderResource\Pages;

use App\Filament\Resources\ServerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerOrders extends ListRecords
{
    protected static string $resource = ServerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
