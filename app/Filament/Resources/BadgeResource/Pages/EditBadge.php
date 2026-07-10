<?php
namespace App\Filament\Resources\BadgeResource\Pages;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\BadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditBadge extends EditRecord
{
    protected static string $resource = BadgeResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
