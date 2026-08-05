<?php

namespace App\Filament\Resources\NdaInvitationResource\Pages;

use App\Filament\Resources\NdaInvitationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNdaInvitations extends ListRecords
{
    protected static string $resource = NdaInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Neuer Vertrag'),
        ];
    }
}
