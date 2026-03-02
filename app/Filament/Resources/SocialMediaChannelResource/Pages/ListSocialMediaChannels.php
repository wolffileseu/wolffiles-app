<?php

namespace App\Filament\Resources\SocialMediaChannelResource\Pages;

use App\Filament\Resources\SocialMediaChannelResource;
use App\Models\SocialMediaChannel;
use App\Services\SocialMedia\SocialMediaService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSocialMediaChannels extends ListRecords
{
    protected static string $resource = SocialMediaChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // Quick broadcast test action
            Actions\Action::make('broadcast_test')
                ->label('Test Broadcast')
                ->icon('heroicon-o-signal')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Test Broadcast to All Active Channels')
                ->modalDescription('This will send a test notification to ALL active channels. Are you sure?')
                ->action(function () {
                    $service = app(SocialMediaService::class);
                    $channels = SocialMediaChannel::active()->get();
                    $successCount = 0;
                    $failCount = 0;

                    foreach ($channels as $channel) {
                        $result = $service->testChannel($channel);
                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $failCount++;
                        }
                    }

                    Notification::make()
                        ->title('Broadcast Test Complete')
                        ->body("✅ {$successCount} successful, ❌ {$failCount} failed")
                        ->color($failCount > 0 ? 'warning' : 'success')
                        ->send();
                }),
        ];
    }
}
