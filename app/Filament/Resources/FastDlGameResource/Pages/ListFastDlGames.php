<?php
namespace App\Filament\Resources\FastDlGameResource\Pages;
use App\Filament\Resources\FastDlGameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFastDlGames extends ListRecords
{
    protected static string $resource = FastDlGameResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
