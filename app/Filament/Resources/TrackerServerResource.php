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
                    Forms\Components\TextInput::make('slug')->maxLength(50)
                        ->regex('/^[a-z][a-z0-9-]+$/')
                        ->rule('not_in:manage,claim,claims,create,edit,delete,admin,new,tracker,servers')
                        ->unique(ignoreRecord: true)
                        ->helperText('Optional public URL slug. Admin bypass: no 30-day lock.'),
                ])->columns(2),

            Forms\Components\Section::make('Public Page Content')
                ->description('Visible on the server\'s public page (/servers/{id})')
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->maxLength(20000)
                        ->columnSpanFull()
                        ->helperText('Markdown + BBCode supported.'),
                    Forms\Components\Textarea::make('rules')
                        ->label('Server Rules')
                        ->rows(6)
                        ->maxLength(20000)
                        ->columnSpanFull()
                        ->helperText('Markdown + BBCode supported.'),
                    Forms\Components\TextInput::make('server_logo_url')->label('Logo URL')->url()->maxLength(500),
                    Forms\Components\TextInput::make('server_banner_url')->label('Banner URL')->url()->maxLength(500),
                    Forms\Components\TextInput::make('server_website')->label('Website')->url()->maxLength(255),
                    Forms\Components\TextInput::make('server_discord')->label('Discord')->maxLength(255),
                    Forms\Components\TextInput::make('server_email')->label('Email')->email()->maxLength(255),
                ])->columns(2),
            Forms\Components\Section::make('Clan Link')
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('claimed_by_clan_id')
                        ->label('Linked clan')
                        ->relationship('clan', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('Linked clan appears on the clan\'s public page.'),
                    Forms\Components\Toggle::make('is_visible_for_clan')
                        ->label('Show on clan page')
                        ->helperText('Toggle visibility independent of link.'),
                ])->columns(2),
            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Toggle::make('is_online'),
                    Forms\Components\Toggle::make('is_manually_added'),
                    Forms\Components\Select::make('status')
                        ->options(['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'removed' => 'Removed', 'banned' => 'Banned'])
                        ->default('active'),
                ])->columns(3),

            Forms\Components\Section::make('Polling')
                ->schema([
                    Forms\Components\TextInput::make('custom_poll_interval')
                        ->label('Custom Poll Interval')
                        ->numeric()
                        ->minValue(15)
                        ->maxValue(3600)
                        ->suffix('seconds')
                        ->placeholder('Auto')
                        ->helperText('Overrides online cadence AND offline backoff. 15–3600 s. Leave empty for default.'),
                    Forms\Components\Toggle::make('polling_paused')
                        ->label('Pause Polling')
                        ->helperText('Server stays in DB but is not polled.'),
                    Forms\Components\Placeholder::make('last_poll_display')
                        ->label('Last Poll')
                        ->content(fn ($record) => $record?->last_poll_at?->diffForHumans() ?? '—'),
                    Forms\Components\Placeholder::make('next_poll_display')
                        ->label('Next Poll')
                        ->content(fn ($record) => $record?->next_poll_at?->diffForHumans() ?? '—'),
                    Forms\Components\Placeholder::make('failures_display')
                        ->label('Consecutive Failures')
                        ->content(fn ($record) => (string) ($record?->poll_failures ?? 0)),
                ])
                ->columns(3),

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
                    ->options(['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'removed' => 'Removed', 'banned' => 'Banned']),
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
