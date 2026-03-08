<?php
namespace App\Filament\Resources\ProfileFieldResource\Pages;
use App\Filament\Resources\ProfileFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListProfileFields extends ListRecords {
    protected static string $resource = ProfileFieldResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
