<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\TrackerClaimResource\Pages\ListTrackerClaims;
use App\Filament\Resources\TrackerClaimResource\Pages\ViewTrackerClaim;
use App\Filament\Resources\TrackerClaimResource\Pages;
use App\Models\Tracker\TrackerClaim;
use App\Models\Tracker\TrackerClan;
use App\Models\Tracker\TrackerPlayer;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Notifications\Notification;

class TrackerClaimResource extends Resource
{
    protected static ?string $model = TrackerClaim::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static string | \UnitEnum | null $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Claims';
    protected static ?string $modelLabel = 'Claim';
    protected static ?string $pluralModelLabel = 'Claims';


    public static function getNavigationBadge(): ?string
    {
        $count = TrackerClaim::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Claim Details')
                ->schema([
                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->required()
                        ->native(false),
                    Textarea::make('review_note')
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
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('claimable_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'player' => 'info',
                        'clan' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('entity_name')
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

                TextColumn::make('user.name')
                    ->label('Claimed By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('proof_type')
                    ->label('Proof')
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst(str_replace('_', ' ', $state)) : '-')
                    ->toggleable(),

                TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->tooltip(fn (TrackerClaim $record) => $record->message)
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('review_note')
                    ->label('Note')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),

                SelectFilter::make('claimable_type')
                    ->label('Type')
                    ->options([
                        'player' => 'Player',
                        'clan' => 'Clan',
                    ]),
            ])
            ->recordActions([
                // Approve action
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Claim')
                    ->modalDescription(fn (TrackerClaim $record) => 'Approve this ' . $record->claimable_type . ' claim by ' . ($record->user->name ?? 'Unknown') . '?')
                    ->schema([
                        Textarea::make('review_note')
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
                    ->schema([
                        Textarea::make('review_note')
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

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Bulk approve
                    BulkAction::make('bulk_approve')
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
                    BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('review_note')
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

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Claim')
                ->schema([
                    TextEntry::make('id')->label('#'),
                    TextEntry::make('claimable_type')->label('Type')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => ucfirst($state))
                        ->color(fn (string $state) => $state === 'player' ? 'info' : 'purple'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray',
                        }),
                    TextEntry::make('created_at')->dateTime('d M Y H:i'),
                ])->columns(4),

            Section::make('Claimant')
                ->schema([
                    TextEntry::make('user.name')->label('User'),
                    TextEntry::make('user.email')->label('Email'),
                    TextEntry::make('proof_type')->label('Proof Type')
                        ->formatStateUsing(fn (?string $state) => $state ? ucfirst(str_replace('_', ' ', $state)) : '-'),
                ])->columns(3),

            Section::make('Message')
                ->schema([
                    TextEntry::make('message')->columnSpanFull(),
                ]),

            Section::make('Clan Details')
                ->schema([
                    TextEntry::make('clan_email')->label('Email')->default('-'),
                    TextEntry::make('clan_website')->label('Website')->default('-'),
                    TextEntry::make('clan_discord')->label('Discord')->default('-'),
                    TextEntry::make('clan_description')->label('Description')->default('-')->columnSpanFull(),
                ])->columns(3)
                ->visible(fn (TrackerClaim $record) => $record->claimable_type === 'clan'),

            Section::make('Review')
                ->schema([
                    TextEntry::make('reviewer.name')->label('Reviewed By')->default('-'),
                    TextEntry::make('reviewed_at')->dateTime('d M Y H:i')->default('-'),
                    TextEntry::make('review_note')->label('Note')->default('-')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackerClaims::route('/'),
            'view' => ViewTrackerClaim::route('/{record}'),
        ];
    }
}
