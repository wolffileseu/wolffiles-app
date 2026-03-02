<?php

namespace App\Filament\Resources\EttvSlotResource\Pages;

use App\Filament\Resources\EttvSlotResource;
use Filament\Resources\Pages\ListRecords;

class ListEttvSlots extends ListRecords
{
    protected static string $resource = EttvSlotResource::class;
    protected static ?string $title = 'ETTV Server Slots';
}
