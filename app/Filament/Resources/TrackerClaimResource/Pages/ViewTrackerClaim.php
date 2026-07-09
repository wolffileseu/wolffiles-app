<?php

namespace App\Filament\Resources\TrackerClaimResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\TrackerClaimResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use App\Models\Tracker\TrackerClaim;
use Filament\Forms;
use Filament\Notifications\Notification;

/** @method TrackerClaim getRecord() */
class ViewTrackerClaim extends ViewRecord
{
    protected static string $resource = TrackerClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Claim')
                ->schema([
                    Textarea::make('review_note')
                        ->label('Note (optional)')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->approve(auth()->id(), $data['review_note'] ?? null);
                    Notification::make()->title('Claim approved!')->success()->send();
                    $this->redirect(TrackerClaimResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('review_note')
                        ->label('Reason (required)')
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->reject(auth()->id(), $data['review_note']);
                    Notification::make()->title('Claim rejected.')->warning()->send();
                    $this->redirect(TrackerClaimResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),

            DeleteAction::make(),
        ];
    }
}
