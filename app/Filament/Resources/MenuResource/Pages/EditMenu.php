<?php
namespace App\Filament\Resources\MenuResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\MenuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
