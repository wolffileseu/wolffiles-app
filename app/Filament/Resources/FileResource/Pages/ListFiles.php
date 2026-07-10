<?php

namespace App\Filament\Resources\FileResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\FileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFiles extends ListRecords
{
    protected static string $resource = FileResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
