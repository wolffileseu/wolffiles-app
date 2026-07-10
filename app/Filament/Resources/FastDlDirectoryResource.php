<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use App\Filament\Resources\FastDlDirectoryResource\Pages\ListFastDlDirectories;
use App\Filament\Resources\FastDlDirectoryResource\Pages\CreateFastDlDirectory;
use App\Filament\Resources\FastDlDirectoryResource\Pages\EditFastDlDirectory;
use App\Filament\Resources\FastDlDirectoryResource\Pages;
use App\Models\FastDl\FastDlDirectory;
use App\Models\FastDl\FastDlGame;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FastDlDirectoryResource extends Resource
{
    protected static ?string $model = FastDlDirectory::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-folder';
    protected static string | \UnitEnum | null $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Directories';
    protected static ?int $navigationSort = 2;


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Directory')->schema([
                Select::make('game_id')
                    ->label('Game')
                    ->options(FastDlGame::where('is_active', true)->pluck('name', 'id'))
                    ->required(),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->required()
                    ->helperText('Folder name in URL: dl.wolffiles.eu/game/{slug}/'),
                Toggle::make('is_base')->default(false)
                    ->helperText('Base directory (etmain) — auto-synced, included in all clan spaces'),
                Toggle::make('is_active')->default(true),
                Textarea::make('description')->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('game.name')->sortable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('slug')->badge()->color('info'),
                TextColumn::make('files_count')->counts('files')->label('Files'),
                IconColumn::make('is_base')->boolean()->label('Base'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('game_id')
                    ->options(FastDlGame::pluck('name', 'id'))
                    ->label('Game'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFastDlDirectories::route('/'),
            'create' => CreateFastDlDirectory::route('/create'),
            'edit' => EditFastDlDirectory::route('/{record}/edit'),
        ];
    }
}
