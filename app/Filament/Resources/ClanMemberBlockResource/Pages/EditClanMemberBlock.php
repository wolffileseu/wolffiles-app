<?php
namespace App\Filament\Resources\ClanMemberBlockResource\Pages;
use App\Filament\Resources\ClanMemberBlockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditClanMemberBlock extends EditRecord
{
    protected static string $resource = ClanMemberBlockResource::class;
    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
