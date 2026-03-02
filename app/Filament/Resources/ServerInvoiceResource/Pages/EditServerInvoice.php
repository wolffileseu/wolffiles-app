<?php

namespace App\Filament\Resources\ServerInvoiceResource\Pages;

use App\Filament\Resources\ServerInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerInvoice extends EditRecord
{
    protected static string $resource = ServerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
