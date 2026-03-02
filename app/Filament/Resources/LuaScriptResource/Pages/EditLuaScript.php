<?php
namespace App\Filament\Resources\LuaScriptResource\Pages;
use App\Filament\Resources\LuaScriptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditLuaScript extends EditRecord
{
    protected static string $resource = LuaScriptResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
