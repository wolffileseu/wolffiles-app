<?php
namespace App\Filament\Resources\FastDlFileResource\Pages;

use App\Filament\Resources\FastDlFileResource;
use App\Models\FastDl\FastDlDirectory;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateFastDlFile extends CreateRecord
{
    protected static string $resource = FastDlFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Als Upload vorhanden, setze s3_path
        if (!empty($data['upload'])) {
            $data['s3_path'] = $data['upload'];

            // Dateigröße von S3 holen
            if (Storage::disk('s3')->exists($data['s3_path'])) {
                $data['file_size'] = Storage::disk('s3')->size($data['s3_path']);
            }
        }

        // Upload-Feld entfernen (ist kein DB-Feld)
        unset($data['upload']);

        return $data;
    }
}
