<?php

namespace App\Filament\Resources\NdaTemplateResource\Pages;

use App\Filament\Resources\NdaTemplateResource;
use App\Models\NdaTemplate;
use Filament\Resources\Pages\CreateRecord;

class CreateNdaTemplate extends CreateRecord
{
    protected static string $resource = NdaTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->deactivateSiblings();
    }

    protected function deactivateSiblings(): void
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
