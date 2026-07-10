<?php

namespace App\Filament\Resources\ServerOrderResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ServerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerOrders extends ListRecords
{
    protected static string $resource = ServerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
