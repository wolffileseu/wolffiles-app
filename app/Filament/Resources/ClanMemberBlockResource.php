<?php
namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ClanMemberBlockResource\Pages\ListClanMemberBlocks;
use App\Filament\Resources\ClanMemberBlockResource\Pages\CreateClanMemberBlock;
use App\Filament\Resources\ClanMemberBlockResource\Pages\EditClanMemberBlock;
use App\Filament\Resources\ClanMemberBlockResource\Pages;
use App\Models\ClanMemberBlock;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClanMemberBlockResource extends Resource
{
    protected static ?string $model = ClanMemberBlock::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-no-symbol';
    protected static string | \UnitEnum | null $navigationGroup = 'Clans';
    protected static ?string $navigationLabel = 'Member Blocks';
    protected static ?int $navigationSort = 30;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('clan_id')
                ->relationship('clan', 'name')
                ->searchable()->preload()->required(),
            Select::make('block_type')
                ->options(['player_id' => 'Player ID', 'name' => 'Name'])
                ->required()
                ->live(),
            Select::make('target_player_id')
                ->label('Target player')
                ->relationship('targetPlayer', 'name_clean')
                ->searchable()
                ->preload()
                ->nullable()
                ->visible(fn ($get) => $get('block_type') === 'player_id'),
            TextInput::make('target_name')
                ->label('Target name')
                ->maxLength(255)
                ->nullable()
                ->visible(fn ($get) => $get('block_type') === 'name'),
            Select::make('blocked_by_user_id')
                ->label('Blocked by')
                ->relationship('blockedBy', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->default(fn () => auth()->id()),
            Textarea::make('reason')->rows(2)->maxLength(500)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clan.name')->label('Clan')->searchable()->sortable(),
                TextColumn::make('block_type')->badge()
                    ->color(fn ($state) => $state === 'player_id' ? 'warning' : 'info'),
                TextColumn::make('target')
                    ->label('Target')
                    ->getStateUsing(fn ($record) => $record->block_type === 'player_id'
                        ? ($record->targetPlayer?->name_clean . ' #' . $record->target_player_id)
                        : $record->target_name)
                    ->searchable(query: function ($query, string $search) {
                        $query->where('target_name', 'like', "%{$search}%")
                              ->orWhereHas('targetPlayer', fn ($q) => $q->where('name_clean', 'like', "%{$search}%"));
                    }),
                TextColumn::make('reason')->limit(40)->tooltip(fn ($record) => $record->reason),
                TextColumn::make('blockedBy.name')->label('By')->sortable(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('clan_id')->relationship('clan', 'name')->label('Clan'),
                SelectFilter::make('block_type')->options(['player_id' => 'Player ID', 'name' => 'Name']),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListClanMemberBlocks::route('/'),
            'create' => CreateClanMemberBlock::route('/create'),
            'edit'   => EditClanMemberBlock::route('/{record}/edit'),
        ];
    }
}
