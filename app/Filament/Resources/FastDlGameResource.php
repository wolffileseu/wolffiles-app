<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use App\Filament\Resources\FastDlGameResource\Pages\ListFastDlGames;
use App\Filament\Resources\FastDlGameResource\Pages\CreateFastDlGame;
use App\Filament\Resources\FastDlGameResource\Pages\EditFastDlGame;
use App\Filament\Resources\FastDlGameResource\Pages;
use App\Models\FastDl\FastDlGame;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FastDlGameResource extends Resource
{
    protected static ?string $model = FastDlGame::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rocket-launch';
    protected static string | \UnitEnum | null $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Games';
    protected static ?int $navigationSort = 1;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Game')->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)
                    ->helperText('URL path: dl.wolffiles.eu/{slug}/'),
                TextInput::make('base_directory')->required()->default('etmain')
                    ->helperText('Main directory name (etmain, main, etc.)'),
                Select::make('game_filter')
                    ->label('Wolffiles Game Filter')
                    ->options(['ET' => 'ET', 'RtCW' => 'RtCW', 'ET Quake Wars' => 'ET Quake Wars', 'ETFortress' => 'ETFortress', 'ET-Domination' => 'ET-Domination'])
                    ->nullable()
                    ->helperText('Which game string to auto-sync from Wolffiles DB'),
                Toggle::make('auto_sync')->default(false)
                    ->helperText('Auto-sync maps from Wolffiles database'),
                Toggle::make('is_active')->default(true),
                Textarea::make('description')->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('slug')->badge()->color('info'),
                TextColumn::make('base_directory'),
                TextColumn::make('directories_count')->counts('directories')->label('Dirs'),
                IconColumn::make('auto_sync')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFastDlGames::route('/'),
            'create' => CreateFastDlGame::route('/create'),
            'edit' => EditFastDlGame::route('/{record}/edit'),
        ];
    }
}
