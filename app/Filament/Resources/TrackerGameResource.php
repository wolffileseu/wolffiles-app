<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackerGameResource\Pages;
use App\Models\Tracker\TrackerGame;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrackerGameResource extends Resource
{
    protected static ?string $model = TrackerGame::class;
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Games';


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_tracker_games');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('short_name')->required()->maxLength(50),
            Forms\Components\TextInput::make('protocol_version')->numeric()->required(),
            Forms\Components\TextInput::make('default_port')->numeric()->default(27960),
            Forms\Components\TextInput::make('query_type')->default('quake3')->maxLength(50),
            Forms\Components\TextInput::make('icon')->maxLength(255),
            Forms\Components\ColorPicker::make('color')->default('#FF9900'),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),

            Forms\Components\Section::make('Master Servers')
                ->schema([
                    Forms\Components\Repeater::make('masterServers')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('address')->required()->placeholder('master.etlegacy.com'),
                            Forms\Components\TextInput::make('port')->numeric()->required()->default(27950),
                            Forms\Components\Toggle::make('is_active')->default(true),
                            Forms\Components\Textarea::make('notes')->rows(2),
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
                Tables\Columns\TextColumn::make('short_name')->label('Game')->sortable(),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\TextColumn::make('protocol_version')->label('Protocol')->sortable(),
                Tables\Columns\TextColumn::make('masterServers_count')->counts('masterServers')->label('Masters'),
                Tables\Columns\TextColumn::make('servers_count')->counts('servers')->label('Servers'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackerGames::route('/'),
            'create' => Pages\CreateTrackerGame::route('/create'),
            'edit' => Pages\EditTrackerGame::route('/{record}/edit'),
        ];
    }
}
