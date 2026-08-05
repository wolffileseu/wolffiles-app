<?php

namespace App\Filament\Resources\Support\TicketResource\Pages;

use App\Filament\Resources\Support\TicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['last_activity_at'] ??= now();

        return $data;
    }
}
