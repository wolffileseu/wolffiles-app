<?php
namespace App\Filament\Resources\FastDlClanResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\FastDlClanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditFastDlClan extends EditRecord
{
    protected static string $resource = FastDlClanResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
