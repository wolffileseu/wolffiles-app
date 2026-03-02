<?php

namespace App\Filament\Resources\ServerInvoiceResource\Pages;

use App\Filament\Resources\ServerInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerInvoices extends ListRecords
{
    protected static string $resource = ServerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
