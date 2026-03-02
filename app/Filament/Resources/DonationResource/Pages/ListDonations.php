<?php
namespace App\Filament\Resources\DonationResource\Pages;
use App\Filament\Resources\DonationResource;
use App\Models\Donation;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDonations extends ListRecords
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add Manual Donation'),
            Actions\Action::make('settings')
                ->label('Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(DonationResource::getUrl('settings')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
