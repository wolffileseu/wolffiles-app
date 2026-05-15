<?php

declare(strict_types=1);

namespace App\Filament\Resources\EtuiModResource\Pages;

use App\Filament\Resources\EtuiModResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEtuiMod extends EditRecord
{
    protected static string $resource = EtuiModResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
