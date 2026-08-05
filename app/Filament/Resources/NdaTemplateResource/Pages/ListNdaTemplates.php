<?php

namespace App\Filament\Resources\NdaTemplateResource\Pages;

use App\Filament\Resources\NdaTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNdaTemplates extends ListRecords
{
    protected static string $resource = NdaTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
