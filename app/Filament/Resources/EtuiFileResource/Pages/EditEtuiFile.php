<?php

declare(strict_types=1);

namespace App\Filament\Resources\EtuiFileResource\Pages;

use App\Filament\Resources\EtuiFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEtuiFile extends EditRecord
{
    protected static string $resource = EtuiFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
