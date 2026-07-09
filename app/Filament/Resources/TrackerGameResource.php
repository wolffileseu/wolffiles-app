<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use App\Filament\Resources\TrackerGameResource\Pages\ListTrackerGames;
use App\Filament\Resources\TrackerGameResource\Pages\CreateTrackerGame;
use App\Filament\Resources\TrackerGameResource\Pages\EditTrackerGame;
use App\Filament\Resources\TrackerGameResource\Pages;
use App\Models\Tracker\TrackerGame;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrackerGameResource extends Resource
{
    protected static ?string $model = TrackerGame::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static string | \UnitEnum | null $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Games';



    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('short_name')->required()->maxLength(50),
            TextInput::make('protocol_version')->numeric()->required(),
            TextInput::make('default_port')->numeric()->default(27960),
            TextInput::make('query_type')->default('quake3')->maxLength(50),
            TextInput::make('icon')->maxLength(255),
            ColorPicker::make('color')->default('#FF9900'),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),

            Section::make('Master Servers')
                ->schema([
                    Repeater::make('masterServers')
                        ->relationship()
                        ->schema([
                            TextInput::make('address')->required()->placeholder('master.etlegacy.com'),
                            TextInput::make('port')->numeric()->required()->default(27950),
                            Toggle::make('is_active')->default(true),
                            Textarea::make('notes')->rows(2),
                        ])
                        ->columns(2)
                        ->defaultItems(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('short_name')->label('Game')->sortable(),
                ColorColumn::make('color'),
                TextColumn::make('protocol_version')->label('Protocol')->sortable(),
                TextColumn::make('masterServers_count')->counts('masterServers')->label('Masters'),
                TextColumn::make('servers_count')->counts('servers')->label('Servers'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrackerGames::route('/'),
            'create' => CreateTrackerGame::route('/create'),
            'edit' => EditTrackerGame::route('/{record}/edit'),
        ];
    }
}
