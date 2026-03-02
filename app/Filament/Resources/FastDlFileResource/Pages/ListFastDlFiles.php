<?php
namespace App\Filament\Resources\FastDlFileResource\Pages;
use App\Filament\Resources\FastDlFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFastDlFiles extends ListRecords
{
    protected static string $resource = FastDlFileResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
