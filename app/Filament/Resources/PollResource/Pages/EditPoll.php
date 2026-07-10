<?php
namespace App\Filament\Resources\PollResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\PollResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditPoll extends EditRecord
{
    protected static string $resource = PollResource::class;

    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
