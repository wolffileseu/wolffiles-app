<?php
namespace App\Filament\Resources\TrackerBanResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\TrackerBanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditTrackerBan extends EditRecord
{
    protected static string $resource = TrackerBanResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
