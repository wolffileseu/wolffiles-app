<?php

namespace App\Filament\Resources\NdaTemplateResource\Pages;

use App\Filament\Resources\NdaTemplateResource;
use App\Models\NdaTemplate;
use Filament\Resources\Pages\EditRecord;

class EditNdaTemplate extends EditRecord
{
    protected static string $resource = NdaTemplateResource::class;

    protected function afterSave(): void
    {
        if (! $this->record->is_active) {
            return;
        }

        NdaTemplate::query()
            ->where('locale', $this->record->locale)
            ->whereKeyNot($this->record->getKey())
            ->update(['is_active' => false]);
    }
}
