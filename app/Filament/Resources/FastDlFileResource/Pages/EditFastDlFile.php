<?php
namespace App\Filament\Resources\FastDlFileResource\Pages;
use App\Filament\Resources\FastDlFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditFastDlFile extends EditRecord
{
    protected static string $resource = FastDlFileResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
