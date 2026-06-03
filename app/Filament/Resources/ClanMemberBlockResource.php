<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ClanMemberBlockResource\Pages;
use App\Models\ClanMemberBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClanMemberBlockResource extends Resource
{
    protected static ?string $model = ClanMemberBlock::class;
    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';
    protected static ?string $navigationGroup = 'Clans';
    protected static ?string $navigationLabel = 'Member Blocks';
    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('clan_id')
                ->relationship('clan', 'name')
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('block_type')
                ->options(['player_id' => 'Player ID', 'name' => 'Name'])
                ->required()
                ->live(),
            Forms\Components\Select::make('target_player_id')
                ->label('Target player')
                ->relationship('targetPlayer', 'name_clean')
                ->searchable()
                ->preload()
                ->nullable()
                ->visible(fn ($get) => $get('block_type') === 'player_id'),
            Forms\Components\TextInput::make('target_name')
                ->label('Target name')
                ->maxLength(255)
                ->nullable()
                ->visible(fn ($get) => $get('block_type') === 'name'),
            Forms\Components\Select::make('blocked_by_user_id')
                ->label('Blocked by')
                ->relationship('blockedBy', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->default(fn () => auth()->id()),
            Forms\Components\Textarea::make('reason')->rows(2)->maxLength(500)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clan.name')->label('Clan')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('block_type')->badge()
                    ->color(fn ($state) => $state === 'player_id' ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('target')
                    ->label('Target')
                    ->getStateUsing(fn ($record) => $record->block_type === 'player_id'
                        ? ($record->targetPlayer?->name_clean . ' #' . $record->target_player_id)
                        : $record->target_name)
                    ->searchable(query: function ($query, string $search) {
                        $query->where('target_name', 'like', "%{$search}%")
                              ->orWhereHas('targetPlayer', fn ($q) => $q->where('name_clean', 'like', "%{$search}%"));
                    }),
                Tables\Columns\TextColumn::make('reason')->limit(40)->tooltip(fn ($record) => $record->reason),
                Tables\Columns\TextColumn::make('blockedBy.name')->label('By')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('clan_id')->relationship('clan', 'name')->label('Clan'),
                Tables\Filters\SelectFilter::make('block_type')->options(['player_id' => 'Player ID', 'name' => 'Name']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClanMemberBlocks::route('/'),
            'create' => Pages\CreateClanMemberBlock::route('/create'),
            'edit'   => Pages\EditClanMemberBlock::route('/{record}/edit'),
        ];
    }
}
