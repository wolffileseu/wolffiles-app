<?php

namespace App\Filament\Resources\FileResource\Pages;

use App\Filament\Resources\FileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFile extends CreateRecord
{
    protected static string $resource = FileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        if (!empty($data['file_path'])) {
            $data['file_name'] = $data['file_name'] ?? basename($data['file_path']);
            $data['file_extension'] = $data['file_extension'] ?? pathinfo($data['file_path'], PATHINFO_EXTENSION);

            try {
                $data['file_size'] = \Storage::disk('s3')->size($data['file_path']);
                $data['mime_type'] = \Storage::disk('s3')->mimeType($data['file_path']);
            } catch (\Exception $e) {
                $data['file_size'] = 0;
                $data['mime_type'] = 'application/octet-stream';
            }
        }
        $data['file_name'] = $data['file_name'] ?? 'unknown';
        $data['file_extension'] = $data['file_extension'] ?? '';
        $data['file_size'] = $data['file_size'] ?? 0;
        $data['file_hash'] = $data['file_hash'] ?? '';
        $data['mime_type'] = $data['mime_type'] ?? 'application/octet-stream';

        return $data;
    }
}
