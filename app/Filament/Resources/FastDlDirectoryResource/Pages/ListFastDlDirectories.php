<?php
namespace App\Filament\Resources\FastDlDirectoryResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\FastDlDirectoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFastDlDirectories extends ListRecords
{
    protected static string $resource = FastDlDirectoryResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
