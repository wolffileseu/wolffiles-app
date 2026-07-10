<?php

namespace App\Filament\Resources\EventResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;
    protected static ?string $title = 'Events';
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
