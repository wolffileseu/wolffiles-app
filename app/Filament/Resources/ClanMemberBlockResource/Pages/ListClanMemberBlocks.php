<?php
namespace App\Filament\Resources\ClanMemberBlockResource\Pages;
use App\Filament\Resources\ClanMemberBlockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListClanMemberBlocks extends ListRecords
{
    protected static string $resource = ClanMemberBlockResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
