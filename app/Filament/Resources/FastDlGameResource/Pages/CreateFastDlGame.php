<?php
namespace App\Filament\Resources\FastDlGameResource\Pages;
use App\Filament\Resources\FastDlGameResource;
use Filament\Resources\Pages\CreateRecord;
class CreateFastDlGame extends CreateRecord
{
    protected static string $resource = FastDlGameResource::class;

    protected function afterCreate(): void
    {
        // Auto-create base directory
        $this->record->directories()->create([
            'name' => ucfirst($this->record->base_directory),
            'slug' => $this->record->base_directory,
            'is_base' => true,
        ]);
    }
}
