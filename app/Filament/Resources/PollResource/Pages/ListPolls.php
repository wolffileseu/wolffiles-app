<?php
namespace App\Filament\Resources\PollResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\PollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListPolls extends ListRecords
{
    protected static string $resource = PollResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }

}
