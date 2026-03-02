<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FastDlClanResource\Pages;
use App\Models\FastDl\FastDlClan;
use App\Models\FastDl\FastDlGame;
use App\Models\FastDl\FastDlDirectory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FastDlClanResource extends Resource
{
    protected static ?string $model = FastDlClan::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Clans';
    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_fastdl_clans');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Clan')->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)
                    ->helperText('URL: dl.wolffiles.eu/{slug}/'),
                Forms\Components\Select::make('game_id')
                    ->label('Game')
                    ->options(FastDlGame::where('is_active', true)->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('leader_user_id')
                    ->label('Clan Leader')
                    ->relationship('leader', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\Toggle::make('include_base')->default(true)
                    ->helperText('Auto-include base directory (etmain) with all maps'),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\TextInput::make('storage_limit_mb')
                    ->label('Storage Limit (MB)')
                    ->numeric()->default(500),
                Forms\Components\Textarea::make('description')->rows(2),
            ])->columns(2),

            Forms\Components\Section::make('Selected Mod Directories')
                ->schema([
                    Forms\Components\CheckboxList::make('selectedDirectories')
                        ->relationship('selectedDirectories', 'name')
                        ->options(function () {
                            return FastDlDirectory::where('is_base', false)
                                ->where('is_active', true)
                                ->with('game')
                                ->get()
                                ->mapWithKeys(fn ($d) => [$d->id => $d->game->name . ' / ' . $d->name]);
                        })
                        ->helperText('Select which mod directories this clan gets access to'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('slug')->badge()->color('info'),
                Tables\Columns\TextColumn::make('game.name')->sortable(),
                Tables\Columns\TextColumn::make('leader.name')->label('Leader'),
                Tables\Columns\TextColumn::make('selected_directories_count')
                    ->counts('selectedDirectories')->label('Mods'),
                Tables\Columns\TextColumn::make('own_files_count')
                    ->counts('ownFiles')->label('Own Files'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFastDlClans::route('/'),
            'create' => Pages\CreateFastDlClan::route('/create'),
            'edit' => Pages\EditFastDlClan::route('/{record}/edit'),
        ];
    }
}
