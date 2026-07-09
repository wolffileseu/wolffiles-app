<?php
namespace App\Filament\Resources\FastDlGameResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\FastDlGameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListFastDlGames extends ListRecords
{
    protected static string $resource = FastDlGameResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
