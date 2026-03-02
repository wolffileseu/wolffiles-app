<?php
namespace App\Filament\Resources\FastDlDirectoryResource\Pages;
use App\Filament\Resources\FastDlDirectoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFastDlDirectories extends ListRecords
{
    protected static string $resource = FastDlDirectoryResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
