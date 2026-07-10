<?php
namespace App\Filament\Resources\TrackerServerResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\TrackerServerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListTrackerServers extends ListRecords
{
    protected static string $resource = TrackerServerResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
