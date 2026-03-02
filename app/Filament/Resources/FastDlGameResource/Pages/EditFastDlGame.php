<?php
namespace App\Filament\Resources\FastDlGameResource\Pages;
use App\Filament\Resources\FastDlGameResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditFastDlGame extends EditRecord
{
    protected static string $resource = FastDlGameResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
