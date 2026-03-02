<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FastDlDirectoryResource\Pages;
use App\Models\FastDl\FastDlDirectory;
use App\Models\FastDl\FastDlGame;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FastDlDirectoryResource extends Resource
{
    protected static ?string $model = FastDlDirectory::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Fast Download';
    protected static ?string $navigationLabel = 'Directories';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_fastdl_directories');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Directory')->schema([
                Forms\Components\Select::make('game_id')
                    ->label('Game')
                    ->options(FastDlGame::where('is_active', true)->pluck('name', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->required()
                    ->helperText('Folder name in URL: dl.wolffiles.eu/game/{slug}/'),
                Forms\Components\Toggle::make('is_base')->default(false)
                    ->helperText('Base directory (etmain) — auto-synced, included in all clan spaces'),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\Textarea::make('description')->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('game.name')->sortable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('slug')->badge()->color('info'),
                Tables\Columns\TextColumn::make('files_count')->counts('files')->label('Files'),
                Tables\Columns\IconColumn::make('is_base')->boolean()->label('Base'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('game_id')
                    ->options(FastDlGame::pluck('name', 'id'))
                    ->label('Game'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFastDlDirectories::route('/'),
            'create' => Pages\CreateFastDlDirectory::route('/create'),
            'edit' => Pages\EditFastDlDirectory::route('/{record}/edit'),
        ];
    }
}
