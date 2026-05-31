<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ClanProposalResource\Pages;
use App\Models\ClanProposal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClanProposalResource extends Resource
{
    protected static ?string $model = ClanProposal::class;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'Clans';
    protected static ?string $navigationLabel = 'Clan Proposals';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $c = static::getModel()::pending()->count();
        return $c > 0 ? (string) $c : null;
    }
    public static function getNavigationBadgeColor(): ?string { return 'warning'; }

    public static function canCreate(): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Proposal')->schema([
                Forms\Components\TextInput::make('tag')->disabled(),
                Forms\Components\TextInput::make('tag_clean')->disabled(),
                Forms\Components\TextInput::make('name')->disabled(),
                Forms\Components\Textarea::make('description')->disabled()->rows(3)->columnSpanFull(),
                Forms\Components\TextInput::make('website')->disabled(),
                Forms\Components\TextInput::make('discord')->disabled(),
            ])->columns(2),

            Forms\Components\Section::make('Submitted by')->schema([
                Forms\Components\Placeholder::make('user')
                    ->label('User')
                    ->content(fn ($record) => $record?->user?->name . ' <' . $record?->user?->email . '>'),
                Forms\Components\Placeholder::make('created_at')
                    ->label('Submitted')
                    ->content(fn ($record) => $record?->created_at?->diffForHumans() . ' (' . $record?->created_at?->format('Y-m-d H:i') . ')'),
            ])->columns(2),

            Forms\Components\Section::make('Review')->schema([
                Forms\Components\TextInput::make('status')->disabled(),
                Forms\Components\Textarea::make('review_note')->rows(2)->columnSpanFull(),
            ])->columns(2)->visible(fn ($record) => $record && $record->status !== ClanProposal::STATUS_PENDING),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tag')->badge()->color('primary')->searchable(),
                Tables\Columns\TextColumn::make('tag_clean')->label('Clean')->searchable(),
                Tables\Columns\TextColumn::make('name')->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('user.name')->label('By')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'merged' => 'info',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved',
                    'merged' => 'Merged', 'rejected' => 'Rejected',
                ])->default('pending'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Creates a tracker_clan (or links to existing if tag_clean matches), then registers the clan with the proposer as owner.')
                    ->form([
                        Forms\Components\Textarea::make('note')->label('Internal note (optional)')->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $record->approve(auth()->id(), $data['note'] ?? null);
                            Notification::make()->title('Proposal approved')
                                ->body($record->status === ClanProposal::STATUS_MERGED
                                    ? 'Merged with existing tracker_clan #' . $record->created_tracker_clan_id
                                    : 'New tracker_clan #' . $record->created_tracker_clan_id . ' created.')
                                ->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Approval failed')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn ($record) => $record->status === ClanProposal::STATUS_PENDING),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('note')->label('Reason')->required()->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject(auth()->id(), $data['note']);
                        Notification::make()->title('Proposal rejected')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status === ClanProposal::STATUS_PENDING),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClanProposals::route('/'),
            'view'  => Pages\ViewClanProposal::route('/{record}'),
        ];
    }
}
