<?php

namespace App\Filament\Resources\TrackerServerResource\Pages;

use App\Filament\Resources\TrackerServerResource;
use App\Jobs\Tracker\PollServerJob;
use App\Services\Tracker\ServerQueryService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTrackerServer extends EditRecord
{
    protected static string $resource = TrackerServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pollNow')
                ->label('Poll Now')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Dispatch an immediate poll on tracker-high queue.')
                ->action(function () {
                    PollServerJob::dispatch($this->record->id)
                        ->onQueue('tracker-high');

                    Notification::make()
                        ->title('Poll dispatched')
                        ->body('Queued on tracker-high — refresh in a few seconds.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('testConnection')
                ->label('Test Connection')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action(function () {
                    $queryService = new ServerQueryService(2, 1);
                    $data = $queryService->queryServer($this->record->ip, $this->record->port);

                    if ($data === null) {
                        Notification::make()
                            ->title('Server unreachable')
                            ->body($this->record->ip . ':' . $this->record->port . ' did not respond.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $settings = $data['settings'] ?? [];
                    $players  = $data['players']  ?? [];
                    $hostname = $settings['sv_hostname'] ?? $settings['hostname'] ?? '?';
                    $map      = $settings['mapname'] ?? '?';
                    $latency  = $data['latency_ms'] ?? '?';

                    Notification::make()
                        ->title('Server reachable')
                        ->body(sprintf(
                            "%s\nMap: %s • Players: %d • Latency: %s ms",
                            mb_strimwidth($hostname, 0, 60, '...'),
                            $map,
                            count($players),
                            $latency
                        ))
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
