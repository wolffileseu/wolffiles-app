<?php
namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Throwable;
use App\Filament\Resources\ClanProposalResource\Pages\ListClanProposals;
use App\Filament\Resources\ClanProposalResource\Pages\ViewClanProposal;
use App\Filament\Resources\ClanProposalResource\Pages;
use App\Models\ClanProposal;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClanProposalResource extends Resource
{
    protected static ?string $model = ClanProposal::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static string | \UnitEnum | null $navigationGroup = 'Clans';
    protected static ?string $navigationLabel = 'Clan Proposals';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $c = static::getModel()::pending()->count();
        return $c > 0 ? (string) $c : null;
    }
    public static function getNavigationBadgeColor(): ?string { return 'warning'; }

    public static function canCreate(): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Proposal')->schema([
                TextInput::make('tag')->disabled(),
                TextInput::make('tag_clean')->disabled(),
                TextInput::make('name')->disabled(),
                Textarea::make('description')->disabled()->rows(3)->columnSpanFull(),
                TextInput::make('website')->disabled(),
                TextInput::make('discord')->disabled(),
            ])->columns(2),

            Section::make('Submitted by')->schema([
                Placeholder::make('user')
                    ->label('User')
                    ->content(fn ($record) => $record?->user?->name . ' <' . $record?->user?->email . '>'),
                Placeholder::make('created_at')
                    ->label('Submitted')
                    ->content(fn ($record) => $record?->created_at?->diffForHumans() . ' (' . $record?->created_at?->format('Y-m-d H:i') . ')'),
            ])->columns(2),

            Section::make('Review')->schema([
                TextInput::make('status')->disabled(),
                Textarea::make('review_note')->rows(2)->columnSpanFull(),
            ])->columns(2)->visible(fn ($record) => $record && $record->status !== ClanProposal::STATUS_PENDING),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tag')->badge()->color('primary')->searchable(),
                TextColumn::make('tag_clean')->label('Clean')->searchable(),
                TextColumn::make('name')->limit(30)->placeholder('—'),
                TextColumn::make('user.name')->label('By')->searchable(),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => match($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'merged' => 'info',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->since()->sortable(),
                TextColumn::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved',
                    'merged' => 'Merged', 'rejected' => 'Rejected',
                ])->default('pending'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Creates a tracker_clan (or links to existing if tag_clean matches), then registers the clan with the proposer as owner.')
                    ->schema([
                        Textarea::make('note')->label('Internal note (optional)')->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $record->approve(auth()->id(), $data['note'] ?? null);
                            Notification::make()->title('Proposal approved')
                                ->body($record->status === ClanProposal::STATUS_MERGED
                                    ? 'Merged with existing tracker_clan #' . $record->created_tracker_clan_id
                                    : 'New tracker_clan #' . $record->created_tracker_clan_id . ' created.')
                                ->success()->send();
                        } catch (Throwable $e) {
                            Notification::make()->title('Approval failed')->body($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn ($record) => $record->status === ClanProposal::STATUS_PENDING),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Reason')->required()->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject(auth()->id(), $data['note']);
                        Notification::make()->title('Proposal rejected')->success()->send();
                    })
                    ->visible(fn ($record) => $record->status === ClanProposal::STATUS_PENDING),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClanProposals::route('/'),
            'view'  => ViewClanProposal::route('/{record}'),
        ];
    }
}
