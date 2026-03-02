<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FastDlGameResource\Pages;
use App\Models\FastDl\FastDlGame;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FastDlGameResource extends Resource
{
    protected static ?string $model = FastDlGame::class;
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Games';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_fastdl_games');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Game')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)
                    ->helperText('URL path: dl.wolffiles.eu/{slug}/'),
                Forms\Components\TextInput::make('base_directory')->required()->default('etmain')
                    ->helperText('Main directory name (etmain, main, etc.)'),
                Forms\Components\Select::make('game_filter')
                    ->label('Wolffiles Game Filter')
                    ->options(['ET' => 'ET', 'RtCW' => 'RtCW', 'ET Quake Wars' => 'ET Quake Wars', 'ETFortress' => 'ETFortress', 'ET-Domination' => 'ET-Domination'])
                    ->nullable()
                    ->helperText('Which game string to auto-sync from Wolffiles DB'),
                Forms\Components\Toggle::make('auto_sync')->default(false)
                    ->helperText('Auto-sync maps from Wolffiles database'),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\Textarea::make('description')->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('slug')->badge()->color('info'),
                Tables\Columns\TextColumn::make('base_directory'),
                Tables\Columns\TextColumn::make('directories_count')->counts('directories')->label('Dirs'),
                Tables\Columns\IconColumn::make('auto_sync')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFastDlGames::route('/'),
            'create' => Pages\CreateFastDlGame::route('/create'),
            'edit' => Pages\EditFastDlGame::route('/{record}/edit'),
        ];
    }
}
