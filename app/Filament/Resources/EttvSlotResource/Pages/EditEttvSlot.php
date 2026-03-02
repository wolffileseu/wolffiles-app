<?php

namespace App\Filament\Resources\EttvSlotResource\Pages;

use App\Filament\Resources\EttvSlotResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditEttvSlot extends EditRecord
{
    protected static string $resource = EttvSlotResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
