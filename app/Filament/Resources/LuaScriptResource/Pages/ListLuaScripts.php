<?php
namespace App\Filament\Resources\LuaScriptResource\Pages;
use Filament\Actions\CreateAction;
use App\Filament\Resources\LuaScriptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListLuaScripts extends ListRecords
{
    protected static string $resource = LuaScriptResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
