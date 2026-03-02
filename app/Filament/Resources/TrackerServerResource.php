<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrackerServerResource\Pages;
use App\Models\Tracker\TrackerServer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrackerServerResource extends Resource
{
    protected static ?string $model = TrackerServer::class;
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationGroup = 'Tracker';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Servers';


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_tracker_servers');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Server')
                ->schema([
                    Forms\Components\Select::make('game_id')
                        ->relationship('game', 'short_name')
                        ->required(),
                    Forms\Components\TextInput::make('ip')->required()->maxLength(45),
                    Forms\Components\TextInput::make('port')->numeric()->required()->default(27960),
                    Forms\Components\TextInput::make('hostname')->maxLength(500),
                    Forms\Components\TextInput::make('hostname_clean')->maxLength(255),
                ])->columns(2),

            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Toggle::make('is_online'),
                    Forms\Components\Toggle::make('is_manually_added'),
                    Forms\Components\Select::make('status')
                        ->options(['active' => 'Active', 'inactive' => 'Inactive', 'removed' => 'Removed', 'banned' => 'Banned'])
                        ->default('active'),
                ])->columns(3),

            Forms\Components\Section::make('Location')
                ->schema([
                    Forms\Components\TextInput::make('country')->maxLength(100),
                    Forms\Components\TextInput::make('country_code')->maxLength(2),
                    Forms\Components\TextInput::make('city')->maxLength(100),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_online')
                    ->boolean()
                    ->label('On')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('game.short_name')->label('Game')->sortable(),
                Tables\Columns\TextColumn::make('hostname_clean')->label('Server')->limit(40)->searchable()->sortable(),
                Tables\Columns\TextColumn::make('ip')->label('IP')->searchable(),
                Tables\Columns\TextColumn::make('current_map')->label('Map')->sortable(),
                Tables\Columns\TextColumn::make('current_players')->label('Players')->sortable()
                    ->formatStateUsing(fn ($record) => $record->current_players . '/' . $record->max_players),
                Tables\Columns\TextColumn::make('country_code')->label('CC')->sortable(),
                Tables\Columns\TextColumn::make('mod_name')->label('Mod')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'removed' => 'danger',
                        'banned' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('current_players', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('game_id')
                    ->relationship('game', 'short_name')
                    ->label('Game'),
                Tables\Filters\TernaryFilter::make('is_online')->label('Online'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'removed' => 'Removed', 'banned' => 'Banned']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrackerServers::route('/'),
            'create' => Pages\CreateTrackerServer::route('/create'),
            'edit' => Pages\EditTrackerServer::route('/{record}/edit'),
        ];
    }
}
