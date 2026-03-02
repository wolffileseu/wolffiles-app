<?php
namespace App\Filament\Resources\TrackerServerResource\Pages;
use App\Filament\Resources\TrackerServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditTrackerServer extends EditRecord
{
    protected static string $resource = TrackerServerResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
