<?php
namespace App\Filament\Resources\FastDlClanResource\Pages;
use App\Filament\Resources\FastDlClanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFastDlClans extends ListRecords
{
    protected static string $resource = FastDlClanResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
