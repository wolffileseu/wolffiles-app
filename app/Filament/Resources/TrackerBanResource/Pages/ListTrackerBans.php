<?php
namespace App\Filament\Resources\TrackerBanResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\TrackerBanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListTrackerBans extends ListRecords
{
    protected static string $resource = TrackerBanResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
