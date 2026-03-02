<?php
namespace App\Filament\Resources\TrackerGameResource\Pages;
use App\Filament\Resources\TrackerGameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListTrackerGames extends ListRecords
{
    protected static string $resource = TrackerGameResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
