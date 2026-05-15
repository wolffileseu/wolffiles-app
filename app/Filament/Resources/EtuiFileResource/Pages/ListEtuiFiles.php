<?php

declare(strict_types=1);

namespace App\Filament\Resources\EtuiFileResource\Pages;

use App\Filament\Resources\EtuiFileResource;
use Database\Seeders\EtuiFileFromFixturesSeeder;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListEtuiFiles extends ListRecords
{
    protected static string $resource = EtuiFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import_fixtures')
                ->label('Import Stock Fixtures')
                ->icon('heroicon-o-folder-arrow-down')
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription('Imports all .menu files from tests/fixtures/etui/{etmain,etlegacy}. Idempotent — unchanged files are skipped via hash.')
                ->action(function (): void {
                    Artisan::call('db:seed', ['--class' => EtuiFileFromFixturesSeeder::class]);
                    Notification::make()
                        ->title('Fixture import complete')
                        ->body(trim(Artisan::output()))
                        ->success()
                        ->send();
                }),
        ];
    }
}
