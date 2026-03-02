<?php

namespace App\Filament\Resources\ServerProductResource\Pages;

use App\Filament\Resources\ServerProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerProducts extends ListRecords
{
    protected static string $resource = ServerProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
