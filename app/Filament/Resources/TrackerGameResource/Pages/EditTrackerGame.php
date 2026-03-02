<?php
namespace App\Filament\Resources\TrackerGameResource\Pages;
use App\Filament\Resources\TrackerGameResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditTrackerGame extends EditRecord
{
    protected static string $resource = TrackerGameResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
