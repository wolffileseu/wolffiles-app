<?php
namespace App\Filament\Resources\FastDlDirectoryResource\Pages;
use App\Filament\Resources\FastDlDirectoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditFastDlDirectory extends EditRecord
{
    protected static string $resource = FastDlDirectoryResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
