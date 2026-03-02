<?php

namespace App\Filament\Resources\FileResource\Pages;

use App\Filament\Resources\FileResource;
use App\Models\FileScreenshot;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

/** @method \App\Models\File getRecord() */
class EditFile extends EditRecord
{
    protected static string $resource = FileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        // Handle new screenshot uploads
        if (!empty($data['new_screenshots'])) {
            $disk = Storage::disk('s3');
            $sortOrder = $this->record->screenshots()->max('sort_order') ?? 0;

            foreach ($data['new_screenshots'] as $tempPath) {
                if (!$disk->exists($tempPath)) {
                    continue;
                }

                $sortOrder++;
                $filename = pathinfo($tempPath, PATHINFO_FILENAME);
                $extension = pathinfo($tempPath, PATHINFO_EXTENSION) ?: 'jpg';
                $newPath = "screenshots/{$this->record->id}/{$filename}.{$extension}";

                // Move from temp to final location
                $disk->move($tempPath, $newPath);

                FileScreenshot::create([
                    'file_id' => $this->record->id,
                    'path' => $newPath,
                    'disk' => 's3',
                    'sort_order' => $sortOrder,
                    'is_primary' => $this->record->screenshots()->count() === 0,
                ]);
            }

            if (count($data['new_screenshots']) > 0) {
                Notification::make()
                    ->title(count($data['new_screenshots']) . ' screenshot(s) added!')
                    ->success()
                    ->send();
            }
        }

        // Handle screenshot deletions
        if (!empty($data['delete_screenshot_ids'])) {
            $ids = collect(explode(',', $data['delete_screenshot_ids']))
                ->map(fn ($id) => (int) trim($id))
                ->filter();

            $screenshots = $this->record->screenshots()->whereIn('id', $ids)->get();
            $count = 0;

            foreach ($screenshots as $screenshot) {
                try {
                    Storage::disk('s3')->delete($screenshot->path);
                    if ($screenshot->thumbnail_path) {
                        Storage::disk('s3')->delete($screenshot->thumbnail_path);
                    }
                } catch (\Exception $e) {
                    // Ignore S3 delete errors
                }
                $screenshot->delete();
                $count++;
            }

            if ($count > 0) {
                Notification::make()
                    ->title("{$count} screenshot(s) deleted!")
                    ->warning()
                    ->send();
            }
        }
    }
}
