<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackerClaimResource\Pages;
use App\Models\Tracker\TrackerClaim;
use App\Models\Tracker\TrackerClan;
use App\Models\Tracker\TrackerPlayer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class TrackerClaimResource extends Resource
{
    protected static ?string $model = TrackerClaim::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Claims';
    protected static ?string $modelLabel = 'Claim';
    protected static ?string $pluralModelLabel = 'Claims';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_tracker_claims');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = TrackerClaim::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Claim Details')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\Textarea::make('review_note')
                        ->label('Moderator Note')
                        ->rows(3)
                        ->maxLength(500),
                ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('claimable_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'player' => 'info',
                        'clan' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('entity_name')
                    ->label('Entity')
                    ->getStateUsing(function (TrackerClaim $record): string {
                        if ($record->claimable_type === 'player') {
                            $player = TrackerPlayer::find($record->claimable_id);
                            return $player ? ($player->name_clean ?? 'Unknown') : 'Deleted';
                        } else {
                            $clan = TrackerClan::find($record->claimable_id);
                            return $clan ? ('[' . $clan->tag_clean . '] ' . ($clan->name ?? '')) : 'Deleted';
                        }
                    })
                    ->searchable(query: function ($query, string $search) {
                        // Search by joining player/clan names
                        $query->where(function ($q) use ($search) {
                            $playerIds = TrackerPlayer::where('name_clean', 'LIKE', "%{$search}%")->pluck('id');
                            $clanIds = TrackerClan::where('tag_clean', 'LIKE', "%{$search}%")
                                ->orWhere('name', 'LIKE', "%{$search}%")->pluck('id');
                            $q->where(function ($q2) use ($playerIds) {
                                $q2->where('claimable_type', 'player')->whereIn('claimable_id', $playerIds);
                            })->orWhere(function ($q2) use ($clanIds) {
                                $q2->where('claimable_type', 'clan')->whereIn('claimable_id', $clanIds);
                            });
                        });
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Claimed By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('proof_type')
                    ->label('Proof')
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst(str_replace('_', ' ', $state)) : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(fn (TrackerClaim $record) => $record->message)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('review_note')
                    ->label('Note')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),

                Tables\Filters\SelectFilter::make('claimable_type')
                    ->label('Type')
                    ->options([
                        'player' => 'Player',
                        'clan' => 'Clan',
                    ]),
            ])
            ->actions([
                // Approve action
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Claim')
                    ->modalDescription(fn (TrackerClaim $record) => 'Approve this ' . $record->claimable_type . ' claim by ' . ($record->user->name ?? 'Unknown') . '?')
                    ->form([
                        Forms\Components\Textarea::make('review_note')
                            ->label('Note (optional)')
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function (TrackerClaim $record, array $data): void {
                        $record->approve(auth()->id(), $data['review_note'] ?? null);
                        Notification::make()
                            ->title('Claim approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (TrackerClaim $record) => $record->status === 'pending'),

                // Reject action
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Claim')
                    ->form([
                        Forms\Components\Textarea::make('review_note')
                            ->label('Reason (required)')
                            ->required()
                            ->rows(2)
                            ->maxLength(500),
                    ])
                    ->action(function (TrackerClaim $record, array $data): void {
                        $record->reject(auth()->id(), $data['review_note']);
                        Notification::make()
                            ->title('Claim rejected')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (TrackerClaim $record) => $record->status === 'pending'),

                // View entity link
                Action::make('view_entity')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(function (TrackerClaim $record): ?string {
                        if ($record->claimable_type === 'player') {
                            $player = TrackerPlayer::find($record->claimable_id);
                            return $player ? route('tracker.player.show', $player) : null;
                        } else {
                            $clan = TrackerClan::find($record->claimable_id);
                            return $clan ? route('tracker.clan.show', $clan) : null;
                        }
                    })
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Bulk approve
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->approve(auth()->id(), 'Bulk approved');
                                }
                            }
                            Notification::make()
                                ->title($records->count() . ' claims approved')
                                ->success()
                                ->send();
                        }),

                    // Bulk reject
                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('review_note')
                                ->label('Reason')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(function ($records, array $data): void {
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->reject(auth()->id(), $data['review_note']);
                                }
                            }
                            Notification::make()
                                ->title($records->count() . ' claims rejected')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Claim')
                ->schema([
                    Infolists\Components\TextEntry::make('id')->label('#'),
                    Infolists\Components\TextEntry::make('claimable_type')->label('Type')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => $state === 'player' ? 'info' : 'purple'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('created_at')->dateTime('d M Y H:i'),
                ])->columns(4),

            Infolists\Components\Section::make('Claimant')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')->label('User'),
                    Infolists\Components\TextEntry::make('user.email')->label('Email'),
                    Infolists\Components\TextEntry::make('proof_type')->label('Proof Type')
                        ->formatStateUsing(fn (?string $state) => $state ? ucfirst(str_replace('_', ' ', $state)) : '-'),
                ])->columns(3),

            Infolists\Components\Section::make('Message')
                ->schema([
                    Infolists\Components\TextEntry::make('message')->columnSpanFull(),
                ]),

            Infolists\Components\Section::make('Clan Details')
                ->schema([
                    Infolists\Components\TextEntry::make('clan_email')->label('Email')->default('-'),
                    Infolists\Components\TextEntry::make('clan_website')->label('Website')->default('-'),
                    Infolists\Components\TextEntry::make('clan_discord')->label('Discord')->default('-'),
                    Infolists\Components\TextEntry::make('clan_description')->label('Description')->default('-')->columnSpanFull(),
                ])->columns(3)
                ->visible(fn (TrackerClaim $record) => $record->claimable_type === 'clan'),

            Infolists\Components\Section::make('Review')
                ->schema([
                    Infolists\Components\TextEntry::make('reviewer.name')->label('Reviewed By')->default('-'),
                    Infolists\Components\TextEntry::make('reviewed_at')->dateTime('d M Y H:i')->default('-'),
                    Infolists\Components\TextEntry::make('review_note')->label('Note')->default('-')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackerClaims::route('/'),
            'view' => Pages\ViewTrackerClaim::route('/{record}'),
        ];
    }
}
