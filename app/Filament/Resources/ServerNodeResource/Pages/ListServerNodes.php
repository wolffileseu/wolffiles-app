<?php

namespace App\Filament\Resources\ServerNodeResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ServerNodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerNodes extends ListRecords
{
    protected static string $resource = ServerNodeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
