<?php

namespace App\Filament\Resources\DonationResource\Pages;

use App\Filament\Resources\DonationResource;
use App\Services\DonationDiscordService;
use App\Services\TelegramNotificationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDonation extends EditRecord
{
    protected static string $resource = DonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('repostDiscord')
                ->label('Repost → Discord')
                ->icon('heroicon-o-megaphone')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Donation erneut auf Discord posten?')
                ->modalDescription('Der Webhook wird mit den aktuellen Daten (z.B. geändertem Namen) erneut gesendet. Die alte Discord-Nachricht bleibt bestehen.')
                ->modalSubmitActionLabel('Ja, jetzt posten')
                ->action(function (DonationDiscordService $discord) {
                    try {
                        $ok = $discord->notify($this->record->fresh());

                        Notification::make()
                            ->title($ok ? 'Discord Post gesendet ✅' : 'Discord Post fehlgeschlagen')
                            ->body($ok ? 'Die Donation wurde im Discord-Kanal erneut gepostet.' : 'Webhook-Antwort war nicht erfolgreich.')
                            ->{$ok ? 'success' : 'warning'}()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Discord Post fehlgeschlagen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('repostTelegram')
                ->label('Repost → Telegram')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Donation erneut auf Telegram posten?')
                ->modalDescription('Die Telegram-Notification wird mit den aktuellen Daten erneut gesendet.')
                ->modalSubmitActionLabel('Ja, jetzt posten')
                ->action(function (TelegramNotificationService $telegram) {
                    try {
                        $telegram->notifyDonation($this->record->fresh());

                        Notification::make()
                            ->title('Telegram Post gesendet ✅')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Telegram Post fehlgeschlagen')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
